
/* ============================================================
   Encounter Form logic
   Dental encounter UI · persistence provided by integration.js
   ============================================================ */

/* ---------- Tooth definitions ---------- */
const ADULT_UPPER = Array.from({length:16},(_,i)=>String(i+1));      // 1..16
const ADULT_LOWER = Array.from({length:16},(_,i)=>String(i+17));     // 17..32
const PED_UPPER   = "ABCDEFGHIJ".split("");                          // A..J
const PED_LOWER   = "KLMNOPQRST".split("");                          // K..T

/* FDI / ISO (World Dental Federation) labels — position-aligned to the Universal arrays above */
const ADULT_UPPER_FDI = ["18","17","16","15","14","13","12","11","21","22","23","24","25","26","27","28"];
const ADULT_LOWER_FDI = ["38","37","36","35","34","33","32","31","41","42","43","44","45","46","47","48"];
const PED_UPPER_FDI   = ["55","54","53","52","51","61","62","63","64","65"];
const PED_LOWER_FDI   = ["75","74","73","72","71","81","82","83","84","85"];

/* Palmer (Zsigmondy-Palmer): per-quadrant 1-8 (permanent) / A-E (primary), with a bracket showing the quadrant.
   Position-aligned digit/letter + quadrant code (UR/UL/LR/LL) for each Universal id. */
const ADULT_UPPER_PALM = ["8","7","6","5","4","3","2","1","1","2","3","4","5","6","7","8"];
const ADULT_LOWER_PALM = ["8","7","6","5","4","3","2","1","1","2","3","4","5","6","7","8"];
const PED_UPPER_PALM   = ["E","D","C","B","A","A","B","C","D","E"];
const PED_LOWER_PALM   = ["E","D","C","B","A","A","B","C","D","E"];
const ADULT_UPPER_QUAD = Array(16).fill(0).map((_,i)=>i<8?"UR":"UL");
const ADULT_LOWER_QUAD = Array(16).fill(0).map((_,i)=>i<8?"LL":"LR");
const PED_UPPER_QUAD   = Array(10).fill(0).map((_,i)=>i<5?"UR":"UL");
const PED_LOWER_QUAD   = Array(10).fill(0).map((_,i)=>i<5?"LL":"LR");

/* Canonical key = Universal id. Build label maps and anatomical order index. */
const FDI_LABEL    = { adult:{}, pediatric:{} };
const PALMER_LABEL = { adult:{}, pediatric:{} };
const PALMER_QUAD  = { adult:{}, pediatric:{} };
const TOOTH_ORDER  = { adult:{}, pediatric:{} };
(function(){
  const seq=[
    [ADULT_UPPER,ADULT_UPPER_FDI,ADULT_UPPER_PALM,ADULT_UPPER_QUAD,"adult"],
    [ADULT_LOWER,ADULT_LOWER_FDI,ADULT_LOWER_PALM,ADULT_LOWER_QUAD,"adult"],
    [PED_UPPER,PED_UPPER_FDI,PED_UPPER_PALM,PED_UPPER_QUAD,"pediatric"],
    [PED_LOWER,PED_LOWER_FDI,PED_LOWER_PALM,PED_LOWER_QUAD,"pediatric"]
  ];
  const idx={adult:0,pediatric:0};
  seq.forEach(([uni,fdi,palm,quad,dent])=>{
    uni.forEach((u,i)=>{
      FDI_LABEL[dent][u]=fdi[i];
      PALMER_LABEL[dent][u]=palm[i];
      PALMER_QUAD[dent][u]=quad[i];
      TOOTH_ORDER[dent][u]=idx[dent]++;
    });
  });
})();
const QUAD_WORDS = {UR:"upper right",UL:"upper left",LR:"lower right",LL:"lower left"};

const ADULT_FINDINGS = ["Sound","Caries","Restoration","Missing","Crown","Bridge abutment","Implant","Root canal treated","Fracture","Mobility","Periapical lesion","Extraction indicated","Watch","Other"];
const PED_FINDINGS   = ["Sound","Caries","Restoration","Missing / exfoliated","Stainless steel crown","Pulpotomy","Pulpectomy","Mobility","Abscess","Extraction indicated","Watch","Space maintainer required","Other"];
const SURFACES       = ["Mesial","Occlusal / Incisal","Distal","Buccal / Facial","Lingual / Palatal"];

/* state keyed by canonical Universal id; separate per dentition. Switching notation only relabels. */
const toothState = { adult:{}, pediatric:{} };
let currentDentition = "adult";
let currentNotation  = "universal";  // "universal" | "fdi" | "palmer"
let activeTooth = null;

/* Plain-text label for a tooth given current notation (used for aria-labels & data) */
function toothLabelText(id,dentition){
  if(currentNotation==="fdi")    return FDI_LABEL[dentition][id]||id;
  if(currentNotation==="palmer"){const q=PALMER_QUAD[dentition][id];return (PALMER_LABEL[dentition][id]||id)+(q?" "+q:"");}
  return id;
}
/* HTML label — renders the true Palmer bracket via CSS borders */
function toothLabelHTML(id,dentition){
  if(currentNotation==="fdi")    return escapeHtml(FDI_LABEL[dentition][id]||id);
  if(currentNotation==="palmer"){
    const d=PALMER_LABEL[dentition][id]||id, q=PALMER_QUAD[dentition][id]||"";
    return '<span class="palmer palmer-'+q+'">'+escapeHtml(d)+'</span>';
  }
  return escapeHtml(id);
}
/* back-compat alias */
function toothLabel(id,dentition){return toothLabelText(id,dentition);}
function notationName(){
  return currentNotation==="fdi"   ? "FDI / ISO (World Dental Federation)"
       : currentNotation==="palmer"? "Palmer Notation Method"
       : "Universal Numbering System";
}

/* ---------- Build tooth chart ---------- */
function buildTooth(id, dentition){
  const div = document.createElement("div");
  div.className = "tooth";
  div.dataset.tooth = id;
  div.dataset.dentition = dentition;
  div.setAttribute("role","button");
  div.setAttribute("tabindex","0");
  const lbl = toothLabelText(id,dentition);
  div.setAttribute("aria-label","Tooth "+lbl);
  div.innerHTML = '<span class="t-dot"></span><span class="t-icon">\uD83E\uDDB7</span><span class="t-id">'+toothLabelHTML(id,dentition)+'</span>';
  div.addEventListener("click",()=>openToothModal(id,dentition));
  div.addEventListener("keydown",e=>{if(e.key==="Enter"||e.key===" "){e.preventDefault();openToothModal(id,dentition);}});
  return div;
}
function renderCharts(){
  const map = [["upperAdult",ADULT_UPPER,"adult"],["lowerAdult",ADULT_LOWER,"adult"],
               ["upperPed",PED_UPPER,"pediatric"],["lowerPed",PED_LOWER,"pediatric"]];
  map.forEach(([elId,arr,dent])=>{
    const el=document.getElementById(elId); el.innerHTML="";
    arr.forEach(id=>{el.appendChild(buildTooth(id,dent)); paintTooth(id,dent);});
  });
  updateChartLabels();
}
function archText(side,arr,dent){
  if(currentNotation==="palmer"){
    const half=arr.length/2;
    const q1=PALMER_QUAD[dent][arr[0]], q2=PALMER_QUAD[dent][arr[arr.length-1]];
    const first=PALMER_LABEL[dent][arr[0]], midA=PALMER_LABEL[dent][arr[half-1]];
    const midB=PALMER_LABEL[dent][arr[half]], last=PALMER_LABEL[dent][arr[arr.length-1]];
    return side+" arch — "+q1+" "+first+" → "+midA+" · "+q2+" "+midB+" → "+last;
  }
  return side+" arch — "+toothLabelText(arr[0],dent)+" → "+toothLabelText(arr[arr.length-1],dent);
}
function updateChartLabels(){
  document.getElementById("archUpperAdult").textContent=archText("Upper",ADULT_UPPER,"adult");
  document.getElementById("archLowerAdult").textContent=archText("Lower",ADULT_LOWER,"adult");
  document.getElementById("archUpperPed").textContent=archText("Upper",PED_UPPER,"pediatric");
  document.getElementById("archLowerPed").textContent=archText("Lower",PED_LOWER,"pediatric");
  const adRange = currentNotation==="fdi"?"11–48":(currentNotation==="palmer"?"1–8 / quadrant":"1–32");
  const pedRange= currentNotation==="fdi"?"51–85":(currentNotation==="palmer"?"A–E / quadrant":"A–T");
  document.getElementById("tabAdult").textContent="Adult permanent ("+adRange+")";
  document.getElementById("tabPed").textContent="Pediatric primary ("+pedRange+")";
}
function switchNotation(which){
  currentNotation = which;
  const btns={universal:"notaUniversal",fdi:"notaFdi",palmer:"notaPalmer"};
  Object.entries(btns).forEach(([key,bid])=>{
    const b=document.getElementById(bid);
    if(!b)return;
    b.classList.toggle("active",key===which);
    b.setAttribute("aria-selected",key===which);
  });
  renderCharts();
  renderSummary();
}
function switchChart(which){
  currentDentition = which;
  document.getElementById("adultChart").hidden = which!=="adult";
  document.getElementById("pediatricChart").hidden = which!=="pediatric";
  document.getElementById("tabAdult").classList.toggle("active",which==="adult");
  document.getElementById("tabPed").classList.toggle("active",which==="pediatric");
  document.getElementById("tabAdult").setAttribute("aria-selected",which==="adult");
  document.getElementById("tabPed").setAttribute("aria-selected",which==="pediatric");
  renderSummary();
}

/* ---------- Tooth modal ---------- */
function openToothModal(id,dentition){
  activeTooth = {id,dentition};
  const findings = dentition==="adult"?ADULT_FINDINGS:PED_FINDINGS;
  const state = toothState[dentition][id] || {findings:[],surfaces:[],notes:""};
  const lbl = toothLabelText(id,dentition);
  document.getElementById("modalToothId").innerHTML = toothLabelHTML(id,dentition);
  document.getElementById("modalToothLabel").textContent = (dentition==="adult"?"Tooth ":"Primary tooth ")+lbl;
  document.getElementById("modalToothSub").textContent = notationName();

  const fWrap = document.getElementById("modalFindings"); fWrap.innerHTML="";
  findings.forEach(f=>{
    const danger = ["Caries","Fracture","Periapical lesion","Extraction indicated","Abscess"].includes(f);
    const l=document.createElement("label"); l.className="chip"+(danger?" danger":"");
    l.innerHTML='<input type="checkbox" value="'+f+'"'+(state.findings.includes(f)?" checked":"")+'><span>'+f+'</span>';
    fWrap.appendChild(l);
  });
  const sWrap = document.getElementById("modalSurfaces"); sWrap.innerHTML="";
  SURFACES.forEach(s=>{
    const l=document.createElement("label"); l.className="chip";
    l.innerHTML='<input type="checkbox" value="'+s+'"'+(state.surfaces.includes(s)?" checked":"")+'><span>'+s+'</span>';
    sWrap.appendChild(l);
  });
  document.getElementById("modalNotes").value = state.notes||"";
  document.getElementById("toothModal").classList.add("open");
}
function closeToothModal(){document.getElementById("toothModal").classList.remove("open");activeTooth=null;}
function saveTooth(){
  if(!activeTooth) return;
  const {id,dentition}=activeTooth;
  const findings=[...document.querySelectorAll("#modalFindings input:checked")].map(i=>i.value);
  const surfaces=[...document.querySelectorAll("#modalSurfaces input:checked")].map(i=>i.value);
  const notes=document.getElementById("modalNotes").value.trim();
  const meaningful = findings.filter(f=>f!=="Sound").length>0 || surfaces.length>0 || notes.length>0 || findings.includes("Sound");
  if(meaningful){ toothState[dentition][id]={findings,surfaces,notes}; }
  else { delete toothState[dentition][id]; }
  paintTooth(id,dentition);
  renderSummary();
  closeToothModal();
}
function clearTooth(){
  if(!activeTooth) return;
  const {id,dentition}=activeTooth;
  delete toothState[dentition][id];
  paintTooth(id,dentition);
  renderSummary();
  closeToothModal();
}
function paintTooth(id,dentition){
  const el=document.querySelector('.tooth[data-tooth="'+id+'"][data-dentition="'+dentition+'"]');
  if(!el) return;
  const st=toothState[dentition][id];
  const hasFinding = st && (st.findings.filter(f=>f!=="Sound").length>0 || st.surfaces.length>0 || st.notes);
  el.classList.toggle("has-finding",!!hasFinding);
  const missing = st && (st.findings.includes("Missing")||st.findings.includes("Missing / exfoliated"));
  el.classList.toggle("t-missing",!!missing);
}

/* ---------- Tooth summary table (prints) ---------- */
function renderSummary(){
  const wrap=document.getElementById("toothSummary");
  const dent=currentDentition;
  const ids=Object.keys(toothState[dent]).sort((a,b)=>{
    const oa=TOOTH_ORDER[dent][a], ob=TOOTH_ORDER[dent][b];
    if(oa!==undefined&&ob!==undefined)return oa-ob;
    return a.localeCompare(b);
  });
  if(ids.length===0){wrap.innerHTML="";return;}
  let rows="";
  ids.forEach(id=>{
    const s=toothState[dent][id];
    rows+='<tr><td style="font-weight:700;color:var(--teal-deep)">'+toothLabelHTML(id,dent)+'</td>'+
          '<td>'+(escapeHtml(s.findings.join(", "))||"—")+'</td>'+
          '<td>'+(escapeHtml(s.surfaces.join(", "))||"—")+'</td>'+
          '<td>'+(s.notes?escapeHtml(s.notes):"—")+'</td></tr>';
  });
  const notaTxt = currentNotation==="fdi"?"FDI / ISO":(currentNotation==="palmer"?"Palmer":"Universal");
  wrap.innerHTML='<span class="subhead">Charted findings — '+(dent==="adult"?"adult permanent":"pediatric primary")+' · '+notaTxt+' notation</span>'+
    '<div class="tbl-wrap"><table class="data"><thead><tr><th style="width:12%">Tooth</th><th style="width:34%">Findings</th><th style="width:27%">Surfaces</th><th>Notes</th></tr></thead><tbody>'+rows+'</tbody></table></div>';
}
function escapeHtml(t){return t.replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));}

/* ---------- Dynamic table rows ---------- */
function delBtn(){return '<td><button type="button" class="row-del" onclick="this.closest(\'tr\').remove();calcBilling()" aria-label="Remove row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button></td>';}

function addDxRow(){
  const tb=document.querySelector("#dxTable tbody");
  const tr=document.createElement("tr");
  tr.innerHTML='<td><input type="text" placeholder="e.g. 14"></td>'+
    '<td><select><option value="">Diagnosis…</option><option>Dental caries</option><option>Reversible pulpitis</option><option>Irreversible pulpitis</option><option>Pulp necrosis</option><option>Symptomatic apical periodontitis</option><option>Asymptomatic apical periodontitis</option><option>Acute apical abscess</option><option>Chronic apical abscess</option><option>Cracked tooth</option><option>Fractured tooth</option><option>Fractured restoration</option><option>Failed restoration</option><option>Periodontal disease</option><option>Gingivitis</option><option>Pericoronitis</option><option>Impacted tooth</option><option>Malocclusion</option><option>Bruxism</option><option>TMJ disorder</option><option>Edentulism</option><option>Denture stomatitis</option><option>Implant complication</option><option>Peri-implantitis</option><option>Oral soft tissue lesion</option><option>Oral infection</option><option>Oral ulceration</option><option>Cosmetic concern</option><option>Dental trauma</option><option>Other</option></select></td>'+
    '<td><select><option value="">Severity…</option><option>Mild</option><option>Moderate</option><option>Severe</option></select></td>'+
    '<td><select><option value="">Prognosis…</option><option>Good</option><option>Fair</option><option>Guarded</option><option>Poor</option><option>Hopeless</option></select></td>'+
    '<td><textarea placeholder="Notes"></textarea></td>'+delBtn();
  tb.appendChild(tr);
}
function addTxRow(){
  const tb=document.querySelector("#txTable tbody");
  const tr=document.createElement("tr");
  tr.innerHTML='<td><input type="text" placeholder="e.g. 30"></td>'+
    '<td><select><option value="">Diagnosis…</option><option>Dental caries</option><option>Reversible pulpitis</option><option>Irreversible pulpitis</option><option>Pulp necrosis</option><option>Symptomatic apical periodontitis</option><option>Asymptomatic apical periodontitis</option><option>Acute apical abscess</option><option>Chronic apical abscess</option><option>Cracked tooth</option><option>Fractured tooth</option><option>Fractured restoration</option><option>Failed restoration</option><option>Periodontal disease</option><option>Gingivitis</option><option>Pericoronitis</option><option>Impacted tooth</option><option>Malocclusion</option><option>Bruxism</option><option>TMJ disorder</option><option>Edentulism</option><option>Denture stomatitis</option><option>Implant complication</option><option>Peri-implantitis</option><option>Oral soft tissue lesion</option><option>Oral infection</option><option>Oral ulceration</option><option>Cosmetic concern</option><option>Dental trauma</option><option>Other</option></select></td>'+
    '<td><input type="text" placeholder="Procedure"></td>'+
    '<td><select><option value="">Priority…</option><option>Immediate</option><option>High</option><option>Medium</option><option>Low</option><option>Elective</option></select></td>'+
    '<td><input type="number" step="0.01" placeholder="0.00"></td>'+
    '<td><textarea placeholder="Notes"></textarea></td>'+delBtn();
  tb.appendChild(tr);
}
function addRxRow(name,dose,freq,dur,qty,refills){
  const tb=document.querySelector("#rxTable tbody");
  const tr=document.createElement("tr");
  const v=s=>s?escapeHtml(String(s)).replace(/"/g,"&quot;"):"";
  tr.innerHTML='<td><input type="text" value="'+v(name)+'" placeholder="Medication"></td>'+
    '<td><input type="text" value="'+v(dose)+'" placeholder="Dose"></td>'+
    '<td><input type="text" value="'+v(freq)+'" placeholder="Freq"></td>'+
    '<td><input type="text" value="'+v(dur)+'" placeholder="Duration"></td>'+
    '<td><input type="text" value="'+v(qty)+'" placeholder="Qty"></td>'+
    '<td><input type="number" value="'+(refills!==undefined?v(refills):"0")+'" style="width:60px"></td>'+
    '<td><input type="text" placeholder="Indication"></td>'+
    '<td><input type="text" placeholder="Instructions"></td>'+delBtn();
  tb.appendChild(tr);
}

/* ---------- Allergy status banner ---------- */
function onAllergyStatus(){
  const band=document.getElementById("allergyBand");
  if(!band)return;
  const sel=document.querySelector('input[name="allergyStatus"]:checked');
  band.classList.remove("is-nkda","is-alert","is-unknown");
  if(!sel)return;
  if(sel.value.indexOf("NKDA")===0)band.classList.add("is-nkda");
  else if(sel.value==="Unknown")band.classList.add("is-unknown");
  else band.classList.add("is-alert");
}

/* ---------- Print prescription only ---------- */
function printRx(){
  const rows=[...document.querySelectorAll("#rxTable tbody tr")].map(tr=>{
    const i=tr.querySelectorAll("input");
    return {med:i[0].value.trim(),dose:i[1].value.trim(),freq:i[2].value.trim(),
            dur:i[3].value.trim(),qty:i[4].value.trim(),refills:i[5].value.trim(),
            ind:i[6].value.trim(),instr:i[7].value.trim()};
  }).filter(r=>r.med);
  if(!rows.length){toast("Add at least one medication before printing.");return;}
  const g=id=>{const el=document.getElementById(id);return el?el.value.trim():"";};
  const allergySel=document.querySelector('input[name="allergyStatus"]:checked');
  const allergy=allergySel?allergySel.value:"Not recorded";
  const allergyDetail=g("mhAllergies");
  const provider=g("signProvider")||g("provider");
  const visitDate=g("visitDate");
  const chart=g("chartNo");
  const enc=g("encounterId");
  const esc=t=>String(t||"").replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
  const alClass=allergy.indexOf("NKDA")===0?"nkda":(allergy==="Unknown"||allergy==="Not recorded"?"unknown":"alert");
  const rxRows=rows.map((r,n)=>`<tr>
      <td class="n">${n+1}</td>
      <td><div class="med">${esc(r.med)}</div>${r.ind?`<div class="ind">for ${esc(r.ind)}</div>`:""}</td>
      <td>${esc(r.dose)||"&mdash;"}</td>
      <td>${esc(r.freq)||"&mdash;"}</td>
      <td>${esc(r.dur)||"&mdash;"}</td>
      <td>${esc(r.qty)||"&mdash;"}</td>
      <td>${esc(r.refills)||"0"}</td>
      <td>${esc(r.instr)||"&mdash;"}</td>
    </tr>`).join("");
  const w=window.open("","_blank","width=820,height=1000");
  if(!w){toast("Allow pop-ups to print.");return;}
  w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Dental medication instructions</title>
  <style>
    *{box-sizing:border-box}
    body{font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2a2a;margin:0;padding:38px 44px}
    .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #0a5856;padding-bottom:14px}
    .clinic{font-size:21px;font-weight:800;color:#0a5856;letter-spacing:.3px}
    .sub{font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#6b7a7a;font-weight:700;margin-top:3px}
    .rx-mark{font-size:46px;font-weight:800;color:#0e7a78;line-height:.8;font-family:Georgia,serif}
    .meta{display:flex;gap:34px;flex-wrap:wrap;margin:18px 0 6px;font-size:13px}
    .meta div span{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#8a9797;font-weight:700;margin-bottom:2px}
    .allergy{margin:14px 0 22px;padding:10px 14px;border-radius:9px;font-size:13px;font-weight:600;border:1.5px solid}
    .allergy.nkda{background:#eef8f1;border-color:#9fd6b4;color:#1f6f47}
    .allergy.alert{background:#fbeae6;border-color:#e09a89;color:#9c3522}
    .allergy.unknown{background:#fdf7e8;border-color:#e3cd92;color:#8a6d1f}
    table{width:100%;border-collapse:collapse;font-size:12.5px}
    th{text-align:left;background:#f1f7f6;color:#0a5856;font-size:10px;letter-spacing:.06em;text-transform:uppercase;padding:9px 8px;border-bottom:2px solid #cfe5e3}
    td{padding:11px 8px;border-bottom:1px solid #e7eded;vertical-align:top}
    td.n{color:#9aa7a7;width:24px}
    .med{font-weight:700;font-size:13.5px}
    .ind{font-size:11px;color:#6b7a7a;margin-top:2px;font-style:italic}
    .sign{margin-top:54px;display:flex;justify-content:space-between;align-items:flex-end}
    .sigline{border-top:1.5px solid #1f2a2a;width:280px;padding-top:6px;font-size:12px;color:#6b7a7a}
    .prov{font-weight:700;color:#1f2a2a;font-size:13.5px}
    .foot{margin-top:30px;font-size:10.5px;color:#9aa7a7;border-top:1px solid #e7eded;padding-top:10px}
    @media print{body{padding:24px 30px}@page{margin:14mm}}
  </style></head><body>
    <div class="head">
      <div><div class="sub">Dental Medication Instructions</div></div>
      <div class="rx-mark">&#8478;</div>
    </div>
    <div class="meta">
      <div><span>Date</span>${esc(visitDate)||"&mdash;"}</div>
      <div><span>Patient</span>${esc(window.dentalConfig.patientName)}</div>
      <div><span>Chart / Patient ID</span>${esc(chart)||"&mdash;"}</div>
      <div><span>Encounter ID</span>${esc(enc)||"&mdash;"}</div>
    </div>
    <div class="allergy ${alClass}">Allergy status: ${esc(allergy)}${allergyDetail?" &mdash; "+esc(allergyDetail):""}</div>
    <table>
      <thead><tr><th></th><th>Medication</th><th>Dose</th><th>Frequency</th><th>Duration</th><th>Quantity</th><th>Refills</th><th>Instructions</th></tr></thead>
      <tbody>${rxRows}</tbody>
    </table>
    <div class="sign">
      <div class="foot" style="border:none;max-width:330px">Medication instructions from the dental encounter. Prescriber must review and sign. This does not create an OpenEMR prescription order.</div>
      <div style="text-align:center"><div class="sigline"></div><div class="prov">${esc(provider)||"Prescriber"}</div><div style="font-size:11px;color:#8a9797">Signature &amp; date</div></div>
    </div>
    <div class="foot">Generated ${new Date().toLocaleString()}</div>
    <script>window.onload=function(){setTimeout(function(){window.print();},250);};<\/script>
  </body></html>`);
  w.document.close();
}

const BILL_CODES=[["D0120", "Periodic oral evaluation"], ["D0140", "Limited oral evaluation, problem-focused"], ["D0150", "Comprehensive oral evaluation, new patient"], ["D0210", "Complete intraoral radiographic series"], ["D0274", "Bitewing X-rays, four images"], ["D0330", "Panoramic radiograph"], ["D1110", "Adult prophylaxis (routine cleaning)"], ["D1120", "Child prophylaxis"], ["D1206", "Fluoride varnish"], ["D1351", "Sealant, per tooth"], ["D2140–D2161", "Amalgam restorations"], ["D2330–D2394", "Resin/composite restorations"], ["D2740", "All-ceramic crown"], ["D2751–D2794", "Crown procedures"], ["D2950", "Core buildup"], ["D3310", "Root canal, anterior tooth"], ["D3320", "Root canal, premolar"], ["D3330", "Root canal, molar"], ["D4341", "Scaling and root planing, four or more teeth per quadrant"], ["D4342", "Scaling and root planing, one to three teeth per quadrant"], ["D4355", "Full-mouth debridement"], ["D4910", "Periodontal maintenance"], ["D7140", "Simple extraction"], ["D7210", "Surgical extraction"], ["D7220–D7241", "Impacted tooth removal"], ["D9230 / D9243", "Nitrous oxide sedation"], ["D9222 / D9223", "Deep sedation/general anesthesia"], ["D9110", "Palliative emergency treatment"], ["D9944", "Occlusal guard, hard appliance"], ["D6010", "Surgical placement of implant"], ["D6056", "Implant-supported porcelain/ceramic crown"], ["D5110", "Complete denture, maxillary"], ["D5120", "Complete denture, mandibular"]];
function billOpts(i){return '<option value="">'+(i?'Select description…':'Select code…')+'</option>'+BILL_CODES.map((c,n)=>'<option value="'+n+'">'+c[i].replace(/&/g,'&amp;')+'</option>').join('');}
function billSync(sel){const tr=sel.closest('tr'),v=sel.value,ss=tr.querySelectorAll('select');ss[0].value=v;ss[1].value=v;}
function addBillRow(){
  const tb=document.querySelector("#billTable tbody");
  const tr=document.createElement("tr");
  tr.innerHTML='<td><select onchange="billSync(this)">'+billOpts(0)+'</select></td>'+
    '<td><select onchange="billSync(this)">'+billOpts(1)+'</select></td>'+
    '<td><input type="number" step="0.01" class="bill-amt" placeholder="0.00" oninput="calcBilling()"></td>'+
    '<td><button type="button" class="row-del" onclick="this.closest(\'tr\').remove();calcBilling()" aria-label="Remove row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button></td>';
  tb.appendChild(tr);
}

/* ---------- Billing calculations (XCD) ---------- */
function calcBilling(){
  let subtotal=0;
  document.querySelectorAll("#billTable .bill-amt").forEach(i=>subtotal+=parseFloat(i.value)||0);
  const discount=parseFloat(document.getElementById("billDiscount").value)||0;
  const tax=parseFloat(document.getElementById("billTax").value)||0;
  const paid=parseFloat(document.getElementById("billPaid").value)||0;
  const total=Math.max(0,subtotal-discount+tax);
  const balance=total-paid;
  document.getElementById("sumSubtotal").textContent=subtotal.toFixed(2);
  document.getElementById("sumTotal").textContent=total.toFixed(2);
  document.getElementById("sumBalance").textContent=balance.toFixed(2);
  document.getElementById("balanceWrap").style.color = balance>0?"var(--danger)":"var(--ok)";
}

/* ---------- File upload display ---------- */
function showFiles(input){
  const target=input.closest(".upload").querySelector("[data-files]");
  if(input.files.length===0){target.textContent="";return;}
  const names=[...input.files].map(f=>f.name);
  target.textContent=(input.files.length>1?input.files.length+" files: ":"")+names.join(", ");
}

/* ---------- Signature pad ---------- */
let sigCtx,sigDrawing=false;
function initSignature(){
  const c=document.getElementById("signaturePad");
  const ratio=window.devicePixelRatio||1;
  const rect=c.getBoundingClientRect();
  c.width=rect.width*ratio; c.height=120*ratio;
  sigCtx=c.getContext("2d"); sigCtx.scale(ratio,ratio);
  sigCtx.strokeStyle="#16302f"; sigCtx.lineWidth=2; sigCtx.lineCap="round"; sigCtx.lineJoin="round";
  const pos=e=>{const r=c.getBoundingClientRect();const t=e.touches?e.touches[0]:e;return{x:t.clientX-r.left,y:t.clientY-r.top};};
  const start=e=>{sigDrawing=true;const p=pos(e);sigCtx.beginPath();sigCtx.moveTo(p.x,p.y);e.preventDefault();};
  const move=e=>{if(!sigDrawing)return;const p=pos(e);sigCtx.lineTo(p.x,p.y);sigCtx.stroke();e.preventDefault();};
  const end=()=>{sigDrawing=false;};
  c.addEventListener("mousedown",start);c.addEventListener("mousemove",move);
  window.addEventListener("mouseup",end);
  c.addEventListener("touchstart",start,{passive:false});c.addEventListener("touchmove",move,{passive:false});
  c.addEventListener("touchend",end);
}
function clearSignature(){const c=document.getElementById("signaturePad");sigCtx&&sigCtx.clearRect(0,0,c.width,c.height);}

/* ---------- Save (serialize → JSON download) ---------- */
function collectData(){
  const data={meta:{dentition:currentDentition,generated:new Date().toISOString(),toothNotation:currentNotation,toothNotationLabel:notationName()},fields:{},groups:{},teeth:toothState,fdiMap:FDI_LABEL,palmerMap:PALMER_LABEL,palmerQuadrant:PALMER_QUAD};
  document.querySelectorAll("#encounterForm input, #encounterForm select, #encounterForm textarea").forEach(el=>{
    if(el.type==="file")return;
    if(el.type==="checkbox"&&el.name){
      (data.groups[el.name]=data.groups[el.name]||[]); if(el.checked)data.groups[el.name].push(el.value);
    } else if(el.type==="radio"){
      if(el.checked&&el.name)data.fields[el.name]=el.value;
    } else if(el.type==="checkbox"&&el.id){
      data.fields[el.id]=el.checked;
    } else if(el.id){
      if(el.value)data.fields[el.id]=el.value;
    }
  });
  // table data
  data.tables={dx:[],tx:[],rx:[],billing:[]};
  document.querySelectorAll("#dxTable tbody tr").forEach(tr=>{
    const c=tr.querySelectorAll("input,select,textarea");
    data.tables.dx.push({tooth:c[0].value,diagnosis:c[1].value,severity:c[2].value,prognosis:c[3].value,notes:c[4].value});
  });
  document.querySelectorAll("#txTable tbody tr").forEach(tr=>{
    const c=tr.querySelectorAll("input,select,textarea");
    data.tables.tx.push({tooth:c[0].value,diagnosis:c[1].value,procedure:c[2].value,priority:c[3].value,cost:c[4].value,notes:c[5].value});
  });
  document.querySelectorAll("#rxTable tbody tr").forEach(tr=>{
    const c=tr.querySelectorAll("input");
    data.tables.rx.push({medication:c[0].value,dose:c[1].value,frequency:c[2].value,duration:c[3].value,quantity:c[4].value,refills:c[5].value,indication:c[6].value,instructions:c[7].value});
  });
  document.querySelectorAll("#billTable tbody tr").forEach(tr=>{
    const c=tr.querySelectorAll("input,select");
    data.tables.billing.push({code:c[0].selectedOptions[0]?.text.replace(/^Select.*/,"")||"",description:c[1].selectedOptions[0]?.text.replace(/^Select.*/,"")||"",amount:c[2].value});
  });
  return data;
}


/* ---------- Validation ---------- */
function validate(){
  let ok=true;
  document.querySelectorAll("[data-req]").forEach(el=>{
    if(!el.value.trim()){el.classList.add("req-empty");ok=false;}
    else el.classList.remove("req-empty");
  });
  return ok;
}
document.querySelectorAll("[data-req]").forEach(el=>el.addEventListener("input",()=>el.classList.remove("req-empty")));

/* ---------- Reset ---------- */


/* ---------- Toast ---------- */
let toastTimer;
function toast(msg){
  const t=document.getElementById("toast");
  document.getElementById("toastMsg").textContent=msg;
  t.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer=setTimeout(()=>t.classList.remove("show"),2600);
}

/* ---------- Active nav highlight ---------- */
function initNavSpy(){
  const links=[...document.querySelectorAll("#navList a")];
  const byId={};links.forEach(a=>byId[a.getAttribute("href").slice(1)]=a);
  const obs=new IntersectionObserver(entries=>{
    entries.forEach(e=>{
      if(e.isIntersecting){
        links.forEach(l=>l.classList.remove("active"));
        const a=byId[e.target.id]; if(a)a.classList.add("active");
      }
    });
  },{rootMargin:"-30% 0px -60% 0px"});
  document.querySelectorAll(".card[id]").forEach(s=>obs.observe(s));
}

/* ---------- Prefill date/time ---------- */
function prefillDateTime(){
  const now=new Date();
  const pad=n=>String(n).padStart(2,"0");
  const d=now.getFullYear()+"-"+pad(now.getMonth()+1)+"-"+pad(now.getDate());
  const t=pad(now.getHours())+":"+pad(now.getMinutes());
  if(!document.getElementById("visitDate").value)document.getElementById("visitDate").value=d;
  if(!document.getElementById("visitTime").value)document.getElementById("visitTime").value=t;
  if(!document.getElementById("signDateTime").value)document.getElementById("signDateTime").value=d+"T"+t;
  if(!document.getElementById("encounterId").value)document.getElementById("encounterId").value="ENC-"+now.getFullYear()+pad(now.getMonth()+1)+pad(now.getDate())+"-"+pad(now.getHours())+pad(now.getMinutes());
}

/* ---------- Init ---------- */
window.addEventListener("DOMContentLoaded",()=>{
  renderCharts();
  initSignature();
  initNavSpy();
  addDxRow();addTxRow();addRxRow();addBillRow();
  calcBilling();
  initializeOpenEmr();
});
document.getElementById("toothModal").addEventListener("click",e=>{if(e.target.id==="toothModal")closeToothModal();});
document.addEventListener("keydown",e=>{if(e.key==="Escape")closeToothModal();});
