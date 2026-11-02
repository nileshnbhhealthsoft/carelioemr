"use strict";
let dentalBaseline = "";
let dentalSavedData = null;
let dentalSaving = false;
let dentalReady = false;

function dentalSnapshot() {
  const data = collectData();
  delete data.meta.generated;
  delete data.fdiMap;
  delete data.palmerMap;
  delete data.palmerQuadrant;
  data.signature = document.getElementById("signaturePad").toDataURL("image/png");
  // Freeze the submitted chart as well as ordinary controls while a request is in flight.
  return structuredClone(data);
}

function dentalStatus(message, error = false) {
  const status = document.getElementById("saveStatus");
  status.textContent = message;
  status.classList.toggle("error", error);
}

function dentalDirty() {
  return dentalReady && JSON.stringify(dentalSnapshot()) !== dentalBaseline;
}

function markDentalChanged() {
  if (dentalReady && !window.dentalConfig.readonly && !dentalSaving) {
    dentalStatus(dentalDirty() ? "Unsaved changes" : (window.dentalConfig.id ? "Saved to encounter" : "New dental record — not saved"));
  }
}

async function restoreDentalData(data) {
  const cfg = window.dentalConfig;
  document.getElementById("encounterForm").reset();
  // reset() restores original checked/default values; explicitly clear all controls for saved records.
  document.querySelectorAll("#encounterForm input, #encounterForm select, #encounterForm textarea").forEach(el => {
    if (el.type === "checkbox" || el.type === "radio") {
      el.checked = data ? false : el.defaultChecked;
    } else if (data && el.id) {
      el.value = el.type === "range" ? "0" : "";
    }
  });
  const fields = data ? data.fields : cfg.defaults;
  for (const [key, value] of Object.entries(fields || {})) {
    const el = document.getElementById(key);
    if (el && el.matches("input, select, textarea")) {
      if (el.type === "checkbox") el.checked = Boolean(value);
      else el.value = value;
    } else {
      document.querySelectorAll("#encounterForm input[type=radio]").forEach(radio => {
        if (radio.name === key) radio.checked = radio.value === value;
      });
    }
  }
  document.querySelectorAll("#encounterForm input[type=checkbox][name]").forEach(el => {
    el.checked = (data?.groups?.[el.name] || []).includes(el.value);
  });
  const tables = [
    ["dx", "dxTable", addDxRow, ["tooth", "diagnosis", "severity", "prognosis", "notes"]],
    ["tx", "txTable", addTxRow, ["tooth", "diagnosis", "procedure", "priority", "cost", "notes"]],
    ["rx", "rxTable", addRxRow, ["medication", "dose", "frequency", "duration", "quantity", "refills", "indication", "instructions"]],
    ["billing", "billTable", addBillRow, ["code", "description", "amount"]],
  ];
  tables.forEach(([key, id, add, columns]) => {
    const tbody = document.querySelector(`#${id} tbody`);
    tbody.replaceChildren();
    const rows = data ? (data.tables?.[key] || []) : [{}];
    rows.forEach(row => {
      add();
      const controls = tbody.lastElementChild.querySelectorAll("input, select, textarea");
      if (key === "billing") {
        const index = BILL_CODES.findIndex(entry => entry[0] === row.code);
        controls[0].value = controls[1].value = index < 0 ? "" : String(index);
        // Retain historical codes if the module's option catalog changes.
        if (row.code && index < 0) {
          [row.code, row.description].forEach((text, n) => {
            controls[n].add(new Option(text, "historical", true, true));
          });
        }
        controls[2].value = row.amount || "";
      } else {
        columns.forEach((column, i) => {
          const value = row[column] ?? "";
          if (controls[i].tagName === "SELECT" && value && ![...controls[i].options].some(o => o.value === value)) {
            controls[i].add(new Option(value, value));
          }
          controls[i].value = value;
        });
      }
    });
  });
  toothState.adult = {...structuredClone(data?.teeth?.adult || {})};
  toothState.pediatric = {...structuredClone(data?.teeth?.pediatric || {})};
  switchNotation(data?.meta?.toothNotation || "universal");
  switchChart(data?.meta?.dentition || "adult");
  document.getElementById("painBadge").textContent = document.getElementById("painScore").value;
  onAllergyStatus();
  calcBilling();
  clearSignature();
  if (data?.signature) {
    await new Promise((resolve, reject) => {
      const image = new Image();
      image.onload = () => {
        const canvas = document.getElementById("signaturePad");
        const ctx = canvas.getContext("2d");
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
        ctx.restore();
        resolve();
      };
      image.onerror = () => reject(new Error("The stored signature could not be restored. Reopen the record before editing."));
      image.src = data.signature;
    });
  }
  ["encounterId", "chartNo"].forEach(id => {
    document.getElementById(id).value = cfg.defaults[id];
    document.getElementById(id).readOnly = true;
  });
}

async function initializeOpenEmr() {
  const cfg = window.dentalConfig;
  document.getElementById("patientContext").textContent =
    `${cfg.patientName} · DOB ${cfg.dob || "not recorded"} · Encounter ${cfg.encounter}`;
  document.getElementById("documentsLink").href = cfg.documentsUrl;
  document.getElementById("returnLink").href = cfg.returnUrl;
  document.getElementById("encounterForm").addEventListener("submit", e => e.preventDefault());
  document.querySelectorAll("[data-save]").forEach(button => { button.disabled = true; });
  try {
    await restoreDentalData(cfg.data);
    dentalSavedData = structuredClone(cfg.data);
    dentalBaseline = JSON.stringify(dentalSnapshot());
    dentalReady = true;
    if (cfg.readonly) {
      document.querySelectorAll("input, select, textarea, button").forEach(el => {
        const action = el.getAttribute("onclick") || "";
        if (!action.includes("print") && !action.includes("switch") && !action.includes("closeToothModal")) {
          el.disabled = true;
        }
      });
      document.getElementById("signaturePad").style.pointerEvents = "none";
      dentalStatus("Read only — signed/locked record or insufficient editing permission");
    } else {
      document.querySelectorAll("[data-save]").forEach(button => { button.disabled = false; });
      dentalStatus(cfg.id ? `Saved to encounter · revision ${cfg.revision}` : "New dental record — not saved");
    }
  } catch (error) {
    dentalStatus(error.message, true);
    document.querySelectorAll("input, select, textarea, button").forEach(el => { el.disabled = true; });
  }
  document.addEventListener("input", markDentalChanged);
  document.addEventListener("change", markDentalChanged);
  document.addEventListener("click", () => setTimeout(markDentalChanged, 0));
  window.addEventListener("mouseup", markDentalChanged);
  window.addEventListener("touchend", markDentalChanged);
  window.addEventListener("beforeunload", event => {
    if (!cfg.readonly && (dentalSaving || dentalDirty())) {
      event.preventDefault();
      event.returnValue = "";
    }
  });
}

async function saveForm() {
  const cfg = window.dentalConfig;
  if (!dentalReady || dentalSaving || cfg.readonly) return;
  if (!validate()) {
    dentalStatus("Complete the highlighted required fields before saving.", true);
    document.querySelector(".req-empty")?.focus();
    return;
  }
  dentalSaving = true;
  document.querySelectorAll("[data-save]").forEach(button => { button.disabled = true; });
  dentalStatus("Saving to OpenEMR…");
  const submitted = dentalSnapshot();
  try {
    const response = await fetch(cfg.saveUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json", "X-CSRF-Token": cfg.csrf },
      body: JSON.stringify({pid: cfg.pid, encounter: cfg.encounter, id: cfg.id, revision: cfg.revision, data: submitted}),
    });
    if (!response.headers.get("content-type")?.includes("application/json")) {
      throw new Error("The session may have expired. Keep this page open and sign in to OpenEMR in another tab before retrying.");
    }
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.error || "The record could not be saved.");
    cfg.id = result.id;
    cfg.revision = result.revision;
    dentalSavedData = structuredClone(submitted);
    dentalBaseline = JSON.stringify(submitted);
    // Refresh/reopen now addresses the saved record, even when launched through load_form.php.
    history.replaceState(null, "", cfg.viewUrl + cfg.id);
    dentalStatus(dentalDirty() ? "Saved submitted changes; newer edits are still unsaved" : `Saved to encounter · revision ${cfg.revision}`);
    toast("Saved to OpenEMR");
  } catch (error) {
    dentalStatus(error.message || "Connection failed. Your entries remain on this page.", true);
  } finally {
    dentalSaving = false;
    document.querySelectorAll("[data-save]").forEach(button => { button.disabled = false; });
  }
}

async function resetForm() {
  if (!dentalReady || dentalSaving || window.dentalConfig.readonly) return;
  if (!confirm("Discard unsaved changes and restore the last saved record?")) return;
  dentalReady = false;
  try {
    await restoreDentalData(dentalSavedData);
    dentalBaseline = JSON.stringify(dentalSnapshot());
    dentalReady = true;
    dentalStatus(window.dentalConfig.id ? "Restored saved record" : "Restored new record defaults");
  } catch (error) {
    dentalStatus(error.message, true);
  }
}

// A full encounter print includes both dentitions, including mixed dentition.
window.addEventListener("beforeprint", () => {
  const previous = currentDentition;
  currentDentition = "adult";
  renderSummary();
  const adult = document.getElementById("toothSummary").innerHTML;
  currentDentition = "pediatric";
  renderSummary();
  const pediatric = document.getElementById("toothSummary").innerHTML;
  currentDentition = previous;
  document.getElementById("toothSummary").innerHTML = adult + pediatric;
});
window.addEventListener("afterprint", renderSummary);
