<?php if (!defined('DENTAL_ENCOUNTER_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>


<!-- ================= HEADER ================= -->
<header class="topbar">
  <div class="topbar-inner">
    <div class="brand">
      <img class="brand-logo" src="<?= attr($assetUrl) ?>/carelioemr-horizontal-tagline-color.svg" alt="CarelioEMR">
      <div class="brand-text">
        <div class="brand-sub">Dental Encounter Record</div>
      </div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-ghost" type="button" onclick="resetForm()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
        <span>Reset</span>
      </button>
      <button class="btn btn-ghost" type="button" onclick="saveForm()" data-save>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
        <span>Save</span>
      </button>
      <button class="btn btn-primary" type="button" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
        <span>Print</span>
      </button>
    </div>
  </div>
</header>

<div class="context-bar"><strong id="patientContext"></strong><span id="saveStatus" role="status" aria-live="polite">Loading encounter…</span><a id="returnLink" class="btn">Return to encounter</a></div><div class="shell">

  <!-- ================= SIDEBAR NAV ================= -->
  <nav class="sidenav" aria-label="Form sections">
    <div class="nav-title">Encounter Sections</div>
    <ul class="nav-list" id="navList">
      <li><a href="#sec1"><span class="nav-num">1</span> Encounter Details</a></li>
      <li><a href="#sec2"><span class="nav-num">2</span> Chief Complaint</a></li>
      <li><a href="#sec3"><span class="nav-num">3</span> Dental History</a></li>
      <li><a href="#sec4"><span class="nav-num">4</span> Medical History</a></li>
      <li><a href="#sec5"><span class="nav-num">5</span> Vitals &amp; Risk</a></li>
      <li><a href="#sec6"><span class="nav-num">6</span> Extraoral Exam</a></li>
      <li><a href="#sec7"><span class="nav-num">7</span> Periodontal</a></li>
      <li><a href="#sec8"><span class="nav-num">8</span> Tooth Charting</a></li>
      <li><a href="#sec9"><span class="nav-num">9</span> Imaging &amp; Uploads</a></li>
      <li><a href="#sec10"><span class="nav-num">10</span> Diagnosis</a></li>
      <li><a href="#sec11"><span class="nav-num">11</span> Treatment Plan</a></li>
      <li><a href="#sec12"><span class="nav-num">12</span> Procedure Today</a></li>
      <li><a href="#sec13"><span class="nav-num">13</span> Anesthesia</a></li>
      <li><a href="#sec14"><span class="nav-num">14</span> Prescriptions</a></li>
      <li><a href="#sec15"><span class="nav-num">15</span> Consent &amp; Education</a></li>
      <li><a href="#sec16"><span class="nav-num">16</span> Referrals</a></li>
      <li><a href="#sec17"><span class="nav-num">17</span> Follow-Up</a></li>
      <li><a href="#sec18"><span class="nav-num">18</span> Billing (XCD)</a></li>
      <li><a href="#sec19"><span class="nav-num">19</span> Sign-Off</a></li>
    </ul>
  </nav>

  <!-- ================= CONTENT ================= -->
  <form class="content" id="encounterForm" novalidate>

  <!-- ===== 1. ENCOUNTER DETAILS ===== -->
  <section class="card" id="sec1">
    <div class="card-head">
      <div class="card-index">1</div>
      <div><div class="card-title">Encounter Details</div><div class="card-desc">Administrative &amp; visit-level information</div></div>
    </div>
    <div class="card-body">
      <div class="grid g4">
        <div class="field"><label class="flabel" for="visitDate">Date of visit <span class="hint">*</span></label><input type="date" id="visitDate" data-req></div>
        <div class="field"><label class="flabel" for="visitTime">Time of visit</label><input type="time" id="visitTime"></div>
        <div class="field"><label class="flabel" for="encounterId">Encounter ID</label><input type="text" id="encounterId" placeholder="ENC-…"></div>
        <div class="field"><label class="flabel" for="chartNo">Chart / Patient ID</label><input type="text" id="chartNo" placeholder="MDS-…"></div>
        <div class="field"><label class="flabel" for="provider">Provider / Dentist <span class="hint">*</span></label><input type="text" id="provider" data-req placeholder="Dr. …"></div>
        <div class="field"><label class="flabel" for="assistant">Hygienist / Assistant</label><input type="text" id="assistant"></div>
        <div class="field"><label class="flabel" for="room">Room / Operatory</label><input type="text" id="room" placeholder="Op 1"></div>
        <div class="field"><label class="flabel" for="referralSource">Referral source</label>
          <select id="referralSource"><option value="">Select…</option><option>Self</option><option>Physician</option><option>Dentist</option><option>Family / friend</option><option>Insurance</option><option>Other</option></select></div>
      </div>
      <div class="grid" style="margin-top:18px">
        <div class="field span-full"><span class="subhead">Visit type</span>
          <div class="radios" role="radiogroup" aria-label="Visit type">
            <label class="radio"><input type="radio" name="visitType" value="General consultation"><span>General consultation</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Emergency"><span>Emergency dental</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Hygiene"><span>Hygiene visit</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Oral surgery consult"><span>Oral surgery consult</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Oral surgery procedure"><span>Oral surgery procedure</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Follow-up"><span>Follow-up</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Cosmetic consult"><span>Cosmetic consult</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Orthodontic consult"><span>Orthodontic consult</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Pediatric dental"><span>Pediatric dental</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Denture / prosthodontic"><span>Denture / prosthodontic</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Implant consult"><span>Implant consult</span></label>
            <label class="radio"><input type="radio" name="visitType" value="Other"><span>Other</span></label>
          </div>
        </div>
        <div class="field span-full"><span class="subhead">Visit priority</span>
          <div class="radios" role="radiogroup" aria-label="Visit priority">
            <label class="radio"><input type="radio" name="priority" value="Routine" checked><span>Routine</span></label>
            <label class="radio warn"><input type="radio" name="priority" value="Urgent"><span>Urgent</span></label>
            <label class="radio alert"><input type="radio" name="priority" value="Emergency"><span>Emergency</span></label>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== 2. CHIEF COMPLAINT ===== -->
  <section class="card" id="sec2">
    <div class="card-head"><div class="card-index">2</div><div><div class="card-title">Chief Complaint</div><div class="card-desc">Presenting concern &amp; pain characterisation</div></div></div>
    <div class="card-body">
      <div class="grid g2">
        <div class="field span2"><label class="flabel" for="ccMain">Main dental concern</label><select id="ccMain"><option value="">Select…</option><option>Tooth pain or sensitivity</option><option>Cavities or broken teeth</option><option>Gum bleeding, swelling, or recession</option><option>Bad breath</option><option>Loose or missing teeth</option><option>Difficulty chewing</option><option>Jaw pain, clicking, or clenching</option><option>Wisdom tooth concerns</option><option>Dry mouth</option><option>Cosmetic concerns (staining, alignment, chips)</option></select></div>
        <div class="field"><label class="flabel" for="ccDuration">Duration of symptoms</label><input type="text" id="ccDuration" placeholder="e.g. 3 days"></div>
        <div class="field"><label class="flabel" for="ccLocation">Pain location</label><select id="ccLocation"><option value="">Select…</option><option>Upper right teeth</option><option>Upper front teeth</option><option>Upper left teeth</option><option>Lower right teeth</option><option>Lower front teeth</option><option>Lower left teeth</option><option>Entire upper jaw</option><option>Entire lower jaw</option><option>Both sides of mouth</option><option>Single tooth</option><option>Multiple teeth</option><option>Gums</option><option>Jaw / temporomandibular joint (TMJ)</option><option>Unable to identify exact location</option></select></div>
      </div>
      <div class="field span-full" style="margin-top:18px"><label class="flabel" for="painScore">Pain score (0–10)</label>
        <div class="pain-wrap">
          <input type="range" id="painScore" class="pain-slider" min="0" max="10" value="0" oninput="document.getElementById('painBadge').textContent=this.value">
          <div class="pain-badge" id="painBadge">0</div>
        </div>
      </div>
      <div class="grid" style="margin-top:18px">
        <div class="field span-full"><span class="subhead">Pain character</span>
          <div class="chips">
            <label class="chip"><input type="checkbox" name="painChar" value="Sharp"><span>Sharp</span></label>
            <label class="chip"><input type="checkbox" name="painChar" value="Dull"><span>Dull</span></label>
            <label class="chip"><input type="checkbox" name="painChar" value="Throbbing"><span>Throbbing</span></label>
            <label class="chip"><input type="checkbox" name="painChar" value="Burning"><span>Burning</span></label>
            <label class="chip"><input type="checkbox" name="painChar" value="Pressure"><span>Pressure</span></label>
            <label class="chip"><input type="checkbox" name="painChar" value="Intermittent"><span>Intermittent</span></label>
            <label class="chip"><input type="checkbox" name="painChar" value="Constant"><span>Constant</span></label>
          </div>
        </div>
        <div class="field span-full"><span class="subhead">Triggers</span>
          <div class="chips">
            <label class="chip"><input type="checkbox" name="trigger" value="Cold"><span>Cold</span></label>
            <label class="chip"><input type="checkbox" name="trigger" value="Heat"><span>Heat</span></label>
            <label class="chip"><input type="checkbox" name="trigger" value="Sweet foods"><span>Sweet foods</span></label>
            <label class="chip"><input type="checkbox" name="trigger" value="Chewing"><span>Chewing</span></label>
            <label class="chip"><input type="checkbox" name="trigger" value="Biting"><span>Biting</span></label>
            <label class="chip"><input type="checkbox" name="trigger" value="Spontaneous"><span>Spontaneous</span></label>
            <label class="chip"><input type="checkbox" name="trigger" value="Night pain"><span>Night pain</span></label>
          </div>
        </div>
        <div class="field span-full"><span class="subhead">Associated findings</span>
          <div class="chips">
            <label class="chip danger"><input type="checkbox" name="ccAssoc" value="Swelling present"><span>Swelling</span></label>
            <label class="chip danger"><input type="checkbox" name="ccAssoc" value="Bleeding present"><span>Bleeding</span></label>
            <label class="chip danger"><input type="checkbox" name="ccAssoc" value="Bad taste / discharge"><span>Bad taste / discharge</span></label>
            <label class="chip"><input type="checkbox" name="ccAssoc" value="Dental trauma history"><span>Trauma history</span></label>
            <label class="chip"><input type="checkbox" name="ccAssoc" value="Lost filling / crown"><span>Lost filling / crown</span></label>
            <label class="chip"><input type="checkbox" name="ccAssoc" value="Broken tooth"><span>Broken tooth</span></label>
            <label class="chip"><input type="checkbox" name="ccAssoc" value="Loose tooth"><span>Loose tooth</span></label>
            <label class="chip"><input type="checkbox" name="ccAssoc" value="Cosmetic concern"><span>Cosmetic concern</span></label>
            <label class="chip"><input type="checkbox" name="ccAssoc" value="Functional concern"><span>Functional concern</span></label>
            <label class="chip"><input type="checkbox" name="ccAssoc" value="Denture concern"><span>Denture concern</span></label>
            <label class="chip"><input type="checkbox" name="ccAssoc" value="Orthodontic concern"><span>Orthodontic concern</span></label>
          </div>
        </div>
        <div class="grid g2 span-full">
          <div class="field"><label class="flabel" for="ccRelieving">Relieving factors</label><input type="text" id="ccRelieving" placeholder="e.g. analgesics, cold water"></div>
          <div class="field"><label class="flabel" for="ccNotes">Free-text notes</label><input type="text" id="ccNotes"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== 3. DENTAL HISTORY ===== -->
  <section class="card" id="sec3">
    <div class="card-head"><div class="card-index">3</div><div><div class="card-title">Dental History</div><div class="card-desc">Past care, habits &amp; hygiene</div></div></div>
    <div class="card-body">
      <div class="grid g3">
        <div class="field"><label class="flabel" for="lastVisit">Last dental visit</label><input type="date" id="lastVisit"></div>
        <div class="field"><label class="flabel" for="lastCleaning">Last cleaning</label><input type="date" id="lastCleaning"></div>
        <div class="field"><label class="flabel" for="lastXray">Last dental X-rays</label><input type="date" id="lastXray"></div>
      </div>
      <div class="field span-full" style="margin-top:18px"><span class="subhead">Previous treatment</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="dhPrev" value="Extractions"><span>Extractions</span></label>
          <label class="chip"><input type="checkbox" name="dhPrev" value="Root canal treatment"><span>Root canal</span></label>
          <label class="chip"><input type="checkbox" name="dhPrev" value="Crowns / bridges"><span>Crowns / bridges</span></label>
          <label class="chip"><input type="checkbox" name="dhPrev" value="Dentures / partials"><span>Dentures / partials</span></label>
          <label class="chip"><input type="checkbox" name="dhPrev" value="Implants"><span>Implants</span></label>
          <label class="chip"><input type="checkbox" name="dhPrev" value="Orthodontic treatment"><span>Orthodontics</span></label>
        </div>
      </div>
      <div class="field span-full" style="margin-top:16px"><span class="subhead">Relevant dental history</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="dhHx" value="Gum disease"><span>Gum disease</span></label>
          <label class="chip"><input type="checkbox" name="dhHx" value="Dental abscess"><span>Dental abscess</span></label>
          <label class="chip"><input type="checkbox" name="dhHx" value="Oral surgery"><span>Oral surgery</span></label>
          <label class="chip"><input type="checkbox" name="dhHx" value="Dental anxiety"><span>Dental anxiety</span></label>
          <label class="chip"><input type="checkbox" name="dhHx" value="Difficult local anesthesia"><span>Difficult anesthesia</span></label>
          <label class="chip"><input type="checkbox" name="dhHx" value="Post-op complications"><span>Post-op complications</span></label>
          <label class="chip"><input type="checkbox" name="dhHx" value="Bruxism / clenching"><span>Bruxism / clenching</span></label>
          <label class="chip"><input type="checkbox" name="dhHx" value="TMJ symptoms"><span>TMJ symptoms</span></label>
        </div>
      </div>
      <div class="field span-full" style="margin-top:16px"><span class="subhead">Oral habits</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="dhHabit" value="Nail biting"><span>Nail biting</span></label>
          <label class="chip"><input type="checkbox" name="dhHabit" value="Pen biting"><span>Pen biting</span></label>
          <label class="chip"><input type="checkbox" name="dhHabit" value="Mouth breathing"><span>Mouth breathing</span></label>
          <label class="chip"><input type="checkbox" name="dhHabit" value="Thumb sucking"><span>Thumb sucking</span></label>
          <label class="chip danger"><input type="checkbox" name="dhHabit" value="Tobacco use"><span>Tobacco use</span></label>
          <label class="chip danger"><input type="checkbox" name="dhHabit" value="Vaping"><span>Vaping</span></label>
          <label class="chip danger"><input type="checkbox" name="dhHabit" value="Betel nut"><span>Betel nut</span></label>
          <label class="chip"><input type="checkbox" name="dhHabit" value="High sugar diet"><span>High sugar diet</span></label>
        </div>
      </div>
      <div class="grid g4" style="margin-top:18px">
        <div class="field"><label class="flabel" for="brushFreq">Brushing frequency</label>
          <select id="brushFreq"><option value="">Select…</option><option>Once daily</option><option>Twice daily</option><option>After every meal</option><option>Irregular</option></select></div>
        <div class="field"><label class="flabel" for="flossFreq">Flossing frequency</label>
          <select id="flossFreq"><option value="">Select…</option><option>Daily</option><option>Few times/week</option><option>Occasionally</option><option>Never</option></select></div>
        <div class="field"><label class="flabel" for="fluoride">Fluoride exposure</label>
          <select id="fluoride"><option value="">Select…</option><option>Fluoridated toothpaste</option><option>Fluoride rinse</option><option>Professional fluoride</option><option>None</option></select></div>
        <div class="field"><label class="flabel">Aids used</label>
          <div class="chips">
            <label class="chip"><input type="checkbox" name="dhAids" value="Mouthwash"><span>Mouthwash</span></label>
            <label class="chip"><input type="checkbox" name="dhAids" value="Electric toothbrush"><span>Electric brush</span></label>
            <label class="chip"><input type="checkbox" name="dhAids" value="Interdental brush"><span>Interdental brush</span></label>
          </div>
        </div>
      </div>
      <div class="grid g2 span-full" style="margin-top:18px">
        <div class="field"><label class="flabel" for="dhGoals">Dental concerns / goals</label><textarea id="dhGoals"></textarea></div>
        <div class="field"><label class="flabel" for="dhNotes">Dental history notes</label><textarea id="dhNotes"></textarea></div>
      </div>
    </div>
  </section>

  <!-- ===== 4. MEDICAL HISTORY ===== -->
  <section class="card" id="sec4">
    <div class="card-head"><div class="card-index">4</div><div><div class="card-title">Medical History Summary</div><div class="card-desc">Concise medical risk screening</div></div></div>
    <div class="card-body">
      <div class="field span-full alert-band" id="allergyBand">
        <span class="subhead">Allergy status <span class="hint">*</span></span>
        <div class="radios">
          <label class="radio ok"><input type="radio" name="allergyStatus" value="NKDA — No Known Drug Allergies" onchange="onAllergyStatus()"><span>NKDA — No Known Drug Allergies</span></label>
          <label class="radio alert"><input type="radio" name="allergyStatus" value="Drug allergies present" onchange="onAllergyStatus()"><span>Drug / other allergies present</span></label>
          <label class="radio warn"><input type="radio" name="allergyStatus" value="Unknown" onchange="onAllergyStatus()"><span>Unknown / not assessed</span></label>
        </div>
      </div>
      <div class="grid g3">
        <div class="field span-full" style="grid-column:1/-1"><div class="grid g3" style="width:100%">
          <div class="field"><label class="flabel" for="mhConditions">Current medical conditions</label><textarea id="mhConditions"></textarea></div>
          <div class="field"><label class="flabel" for="mhMeds">Current medications</label><textarea id="mhMeds"></textarea></div>
          <div class="field"><label class="flabel" for="mhAllergies">Allergy detail</label><textarea id="mhAllergies" placeholder="List all known allergies & reactions"></textarea></div>
        </div></div>
      </div>
      <div class="field span-full" style="margin-top:18px"><span class="subhead">Allergy flags</span>
        <div class="chips">
          <label class="chip danger"><input type="checkbox" name="mhAllergy" value="Latex allergy"><span>Latex</span></label>
          <label class="chip danger"><input type="checkbox" name="mhAllergy" value="Local anesthetic allergy"><span>Local anesthetic</span></label>
          <label class="chip danger"><input type="checkbox" name="mhAllergy" value="Antibiotic allergy"><span>Antibiotic</span></label>
        </div>
      </div>
      <div class="field span-full" style="margin-top:16px"><span class="subhead">Systemic conditions</span>
        <div class="chips">
          <label class="chip danger"><input type="checkbox" name="mhCond" value="Bleeding disorder"><span>Bleeding disorder</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Heart disease"><span>Heart disease</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Hypertension"><span>Hypertension</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Diabetes"><span>Diabetes</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Asthma / COPD"><span>Asthma / COPD</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Kidney disease"><span>Kidney disease</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Liver disease"><span>Liver disease</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Epilepsy / seizures"><span>Epilepsy / seizures</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Stroke / TIA"><span>Stroke / TIA</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Cancer history"><span>Cancer history</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Radiation therapy"><span>Radiation therapy</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Chemotherapy"><span>Chemotherapy</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Osteoporosis / bisphosphonates"><span>Bisphosphonates</span></label>
          <label class="chip danger"><input type="checkbox" name="mhCond" value="Blood thinners / anticoagulants"><span>Anticoagulants</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="Immunosuppression"><span>Immunosuppression</span></label>
          <label class="chip"><input type="checkbox" name="mhCond" value="HIV / hepatitis / TB"><span>HIV / hepatitis / TB</span></label>
        </div>
      </div>
      <div class="field span-full" style="margin-top:16px"><span class="subhead">Cardiac / surgical risk flags</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="mhCardiac" value="Artificial heart valve"><span>Artificial heart valve</span></label>
          <label class="chip"><input type="checkbox" name="mhCardiac" value="Pacemaker"><span>Pacemaker</span></label>
          <label class="chip"><input type="checkbox" name="mhCardiac" value="Joint replacement"><span>Joint replacement</span></label>
          <label class="chip danger"><input type="checkbox" name="mhCardiac" value="History of infective endocarditis"><span>Hx infective endocarditis</span></label>
          <label class="chip danger"><input type="checkbox" name="mhCardiac" value="Antibiotic prophylaxis required"><span>Antibiotic prophylaxis req.</span></label>
        </div>
      </div>
      <div class="grid g4" style="margin-top:18px">
        <div class="field"><label class="flabel" for="mhPregnancy">Pregnancy status</label>
          <select id="mhPregnancy"><option value="">N/A</option><option>Not pregnant</option><option>Pregnant — 1st trimester</option><option>Pregnant — 2nd trimester</option><option>Pregnant — 3rd trimester</option><option>Unknown</option></select></div>
        <div class="field"><label class="flabel" for="mhBreastfeeding">Breastfeeding</label>
          <select id="mhBreastfeeding"><option value="">N/A</option><option>No</option><option>Yes</option></select></div>
        <div class="field span2"><label class="flabel" for="mhHosp">Hospitalisations / surgeries</label><input type="text" id="mhHosp"></div>
      </div>
      <div class="grid g2" style="margin-top:18px">
        <div class="field"><span class="subhead">Medical clearance required</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="mhClearance" value="No" checked><span>No</span></label>
            <label class="radio warn"><input type="radio" name="mhClearance" value="Yes"><span>Yes</span></label>
            <label class="radio"><input type="radio" name="mhClearance" value="Already obtained"><span>Already obtained</span></label>
          </div>
        </div>
        <div class="field"><label class="flabel" for="mhNotes">Medical risk notes</label><textarea id="mhNotes"></textarea></div>
      </div>
    </div>
  </section>

  <!-- ===== 5. VITALS & RISK ===== -->
  <section class="card" id="sec5">
    <div class="card-head"><div class="card-index">5</div><div><div class="card-title">Vitals &amp; Risk Assessment</div><div class="card-desc">Baseline observations &amp; sedation risk</div></div></div>
    <div class="card-body">
      <div class="grid g4">
        <div class="field"><label class="flabel" for="vBP">Blood pressure</label><input type="text" id="vBP" placeholder="120/80 mmHg"></div>
        <div class="field"><label class="flabel" for="vPulse">Pulse (bpm)</label><input type="number" id="vPulse"></div>
        <div class="field"><label class="flabel" for="vRR">Respiratory rate</label><input type="number" id="vRR"></div>
        <div class="field"><label class="flabel" for="vTemp">Temperature (°C)</label><input type="number" step="0.1" id="vTemp"></div>
        <div class="field"><label class="flabel" for="vSpO2">Oxygen saturation (%)</label><input type="number" id="vSpO2"></div>
        <div class="field"><label class="flabel" for="vGlucose">Blood glucose (if diabetic)</label><input type="text" id="vGlucose" placeholder="mmol/L"></div>
        <div class="field"><label class="flabel" for="vWeight">Weight (kg, if needed)</label><input type="number" step="0.1" id="vWeight"></div>
      </div>
      <div class="grid g2" style="margin-top:18px">
        <div class="field"><span class="subhead">ASA classification</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="asa" value="ASA I"><span>ASA I</span></label>
            <label class="radio"><input type="radio" name="asa" value="ASA II"><span>ASA II</span></label>
            <label class="radio warn"><input type="radio" name="asa" value="ASA III"><span>ASA III</span></label>
            <label class="radio alert"><input type="radio" name="asa" value="ASA IV"><span>ASA IV</span></label>
            <label class="radio alert"><input type="radio" name="asa" value="ASA V"><span>ASA V</span></label>
          </div>
        </div>
        <div class="field"><span class="subhead">Dental anxiety level</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="anxiety" value="None"><span>None</span></label>
            <label class="radio"><input type="radio" name="anxiety" value="Mild"><span>Mild</span></label>
            <label class="radio warn"><input type="radio" name="anxiety" value="Moderate"><span>Moderate</span></label>
            <label class="radio alert"><input type="radio" name="anxiety" value="Severe"><span>Severe</span></label>
          </div>
        </div>
      </div>
      <div class="grid g2" style="margin-top:18px">
        <div class="field"><label class="flabel" for="sedationNotes">Sedation risk notes</label><textarea id="sedationNotes"></textarea></div>
        <div class="field"><label class="flabel" for="riskNotes">Medical risk notes</label><textarea id="riskNotes"></textarea></div>
      </div>
    </div>
  </section>

  <!-- ===== 6. EXTRAORAL EXAM ===== -->
  <section class="card" id="sec6">
    <div class="card-head"><div class="card-index">6</div><div><div class="card-title">Extraoral Examination</div><div class="card-desc">Face, TMJ, lymphatics &amp; skin</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Findings (tick if abnormal / present)</span>
        <div class="chips">
          <label class="chip danger"><input type="checkbox" name="extraoral" value="Facial asymmetry"><span>Facial asymmetry</span></label>
          <label class="chip danger"><input type="checkbox" name="extraoral" value="Facial swelling"><span>Facial swelling</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="Facial tenderness"><span>Facial tenderness</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="Lymph node enlargement"><span>Lymphadenopathy</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="TMJ tenderness"><span>TMJ tenderness</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="TMJ clicking / popping"><span>TMJ clicking</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="Limited mouth opening"><span>Limited opening</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="Muscle tenderness"><span>Muscle tenderness</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="Lip abnormality"><span>Lip abnormality</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="Skin lesions"><span>Skin lesions</span></label>
          <label class="chip danger"><input type="checkbox" name="extraoral" value="Sinus tract"><span>Sinus tract</span></label>
          <label class="chip"><input type="checkbox" name="extraoral" value="Trauma / bruising"><span>Trauma / bruising</span></label>
        </div>
      </div>
      <div class="field span-full" style="margin-top:18px"><label class="flabel" for="extraoralNotes">Extraoral examination notes</label><textarea id="extraoralNotes" placeholder="Describe abnormal findings, laterality, dimensions…"></textarea></div>
    </div>
  </section>


  <!-- ===== 8. PERIODONTAL ===== -->
  <section class="card" id="sec7">
    <div class="card-head"><div class="card-index">7</div><div><div class="card-title">Periodontal Assessment</div><div class="card-desc">Screening, diagnosis, staging &amp; grading</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Periodontal screening completed</span>
        <div class="radios">
          <label class="radio"><input type="radio" name="perioScreen" value="Yes"><span>Yes</span></label>
          <label class="radio"><input type="radio" name="perioScreen" value="No"><span>No</span></label>
          <label class="radio"><input type="radio" name="perioScreen" value="Deferred"><span>Deferred</span></label>
        </div>
      </div>
      <div class="grid g4" style="margin-top:18px">
        <div class="field"><label class="flabel" for="pdDepths">Pocket depths</label><input type="text" id="pdDepths" placeholder="e.g. 3–5mm generalised"></div>
        <div class="field"><label class="flabel" for="pdBleeding">Bleeding points</label><input type="text" id="pdBleeding"></div>
        <div class="field"><label class="flabel" for="pdRecession">Recession</label><input type="text" id="pdRecession"></div>
        <div class="field"><label class="flabel" for="pdMobility">Mobility</label><input type="text" id="pdMobility" placeholder="Grade 0–III"></div>
        <div class="field"><label class="flabel" for="pdFurcation">Furcation involvement</label><input type="text" id="pdFurcation"></div>
        <div class="field"><label class="flabel" for="pdPlaqueScore">Plaque score (%)</label><input type="number" id="pdPlaqueScore"></div>
        <div class="field"><label class="flabel" for="pdCalcScore">Calculus score</label><input type="text" id="pdCalcScore"></div>
      </div>
      <div class="grid g3" style="margin-top:18px">
        <div class="field"><label class="flabel" for="perioDx">Gingival / periodontal diagnosis</label>
          <select id="perioDx"><option value="">Select…</option><option>Healthy</option><option>Gingivitis</option><option>Localized periodontitis</option><option>Generalized periodontitis</option><option>Peri-implant mucositis</option><option>Peri-implantitis</option></select></div>
        <div class="field"><label class="flabel" for="perioStage">Staging</label>
          <select id="perioStage"><option value="">Select…</option><option>Stage I</option><option>Stage II</option><option>Stage III</option><option>Stage IV</option><option>Not applicable</option></select></div>
        <div class="field"><label class="flabel" for="perioGrade">Grading</label>
          <select id="perioGrade"><option value="">Select…</option><option>Grade A</option><option>Grade B</option><option>Grade C</option><option>Not applicable</option></select></div>
      </div>
      <div class="field span-full" style="margin-top:18px"><span class="subhead">Recommended periodontal treatment</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="perioTx" value="Oral hygiene instruction"><span>OHI</span></label>
          <label class="chip"><input type="checkbox" name="perioTx" value="Prophylaxis"><span>Prophylaxis</span></label>
          <label class="chip"><input type="checkbox" name="perioTx" value="Scaling and root planing"><span>Scaling &amp; root planing</span></label>
          <label class="chip"><input type="checkbox" name="perioTx" value="Periodontal maintenance"><span>Periodontal maintenance</span></label>
          <label class="chip"><input type="checkbox" name="perioTx" value="Periodontal referral"><span>Periodontal referral</span></label>
          <label class="chip"><input type="checkbox" name="perioTx" value="Surgical periodontal treatment"><span>Surgical treatment</span></label>
          <label class="chip"><input type="checkbox" name="perioTx" value="Other"><span>Other</span></label>
        </div>
      </div>
      <div class="field span-full" style="margin-top:16px"><label class="flabel" for="perioNotes">Periodontal chart notes</label><textarea id="perioNotes"></textarea></div>
    </div>
  </section>

  <!-- ===== 9/10. TOOTH CHARTING ===== -->
  <section class="card" id="sec8">
    <div class="card-head"><div class="card-index">8</div><div><div class="card-title">Tooth Charting</div><div class="card-desc">Choose a notation system and dentition, then tap a tooth to record findings.</div></div></div>
    <div class="card-body">
      <div class="chart-controls">
        <div class="chart-toggle" role="tablist" aria-label="Notation system">
          <button type="button" id="notaUniversal" class="active" role="tab" aria-selected="true" onclick="switchNotation('universal')">Universal (US)</button>
          <button type="button" id="notaFdi" role="tab" aria-selected="false" onclick="switchNotation('fdi')">FDI / ISO</button>
          <button type="button" id="notaPalmer" role="tab" aria-selected="false" onclick="switchNotation('palmer')">Palmer</button>
        </div>
        <div class="chart-toggle" role="tablist" aria-label="Dentition">
          <button type="button" id="tabAdult" class="active" role="tab" aria-selected="true" onclick="switchChart('adult')">Adult permanent</button>
          <button type="button" id="tabPed" role="tab" aria-selected="false" onclick="switchChart('pediatric')">Pediatric primary</button>
        </div>
      </div>

      <!-- ADULT -->
      <div id="adultChart">
        <div class="arch">
          <div class="arch-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v18M3 12h18"/></svg> <span id="archUpperAdult">Upper arch</span></div>
          <div class="teeth" id="upperAdult"></div>
        </div>
        <div class="arch">
          <div class="arch-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v18M3 12h18"/></svg> <span id="archLowerAdult">Lower arch</span></div>
          <div class="teeth" id="lowerAdult"></div>
        </div>
        <div class="grid g2" style="margin-top:18px">
          <div class="field"><label class="flabel" for="adultDentitionStage">Eruption / dentition stage</label>
            <select id="adultDentitionStage"><option value="">Select…</option><option>Primary dentition</option><option>Mixed dentition</option><option>Permanent dentition</option></select></div>
          <div class="field"><label class="flabel" for="adultChartNotes">General adult charting notes</label><input type="text" id="adultChartNotes"></div>
        </div>
      </div>

      <!-- PEDIATRIC -->
      <div id="pediatricChart" hidden>
        <div class="arch">
          <div class="arch-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v18M3 12h18"/></svg> <span id="archUpperPed">Upper arch</span></div>
          <div class="teeth" id="upperPed"></div>
        </div>
        <div class="arch">
          <div class="arch-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v18M3 12h18"/></svg> <span id="archLowerPed">Lower arch</span></div>
          <div class="teeth" id="lowerPed"></div>
        </div>
        <div class="grid g3" style="margin-top:18px">
          <div class="field"><label class="flabel" for="pedStage">Eruption / dentition stage</label>
            <select id="pedStage"><option value="">Select…</option><option>Primary dentition</option><option>Mixed dentition</option><option>Permanent dentition</option></select></div>
          <div class="field"><label class="flabel" for="pedBehavior">Behavior rating</label>
            <select id="pedBehavior"><option value="">Select…</option><option>Excellent</option><option>Good</option><option>Fair</option><option>Poor</option><option>Unable to complete exam</option></select></div>
          <div class="field"><label class="flabel" for="pedCariesRisk">Caries risk</label>
            <select id="pedCariesRisk"><option value="">Select…</option><option>Low</option><option>Moderate</option><option>High</option><option>Extreme</option></select></div>
        </div>
        <div class="field span-full" style="margin-top:16px"><label class="flabel" for="pedChartNotes">Pediatric charting notes</label><input type="text" id="pedChartNotes"></div>
      </div>

      <div class="chart-legend">
        <span><span class="lg-dot"></span> Tooth with recorded findings</span>
        <span>● Click any tooth to add caries, restorations, surfaces &amp; notes</span>
        <span>Multiple findings per tooth supported</span>
      </div>

      <!-- summary table generated by JS -->
      <div class="tooth-summary" id="toothSummary"></div>
    </div>
  </section>


  <!-- ===== 12. IMAGING & UPLOADS ===== -->
  <section class="card" id="sec9">
    <div class="card-head"><div class="card-index">9</div><div><div class="card-title">Radiographs, Imaging &amp; Uploads</div><div class="card-desc">Imaging acquired today, findings &amp; file attachments</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Imaging taken today</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="imgTaken" value="Bitewings"><span>Bitewings</span></label>
          <label class="chip"><input type="checkbox" name="imgTaken" value="Periapical"><span>Periapical</span></label>
          <label class="chip"><input type="checkbox" name="imgTaken" value="Panoramic"><span>Panoramic</span></label>
          <label class="chip"><input type="checkbox" name="imgTaken" value="CBCT"><span>CBCT</span></label>
          <label class="chip"><input type="checkbox" name="imgTaken" value="Intraoral photos"><span>Intraoral photos</span></label>
          <label class="chip"><input type="checkbox" name="imgTaken" value="Extraoral photos"><span>Extraoral photos</span></label>
          <label class="chip"><input type="checkbox" name="imgTaken" value="Occlusal radiograph"><span>Occlusal</span></label>
          <label class="chip"><input type="checkbox" name="imgTaken" value="Cephalometric"><span>Cephalometric</span></label>
          <label class="chip"><input type="checkbox" name="imgTaken" value="None"><span>None</span></label>
        </div>
      </div>

      <div class="field span-full"><span class="subhead">Images and attachments</span><p>Upload dental images and other attachments in this patient’s OpenEMR Documents area.</p><a class="btn" id="documentsLink" target="_blank" rel="noopener">Open patient documents</a></div>

      <div class="field span-full" style="margin-top:20px"><span class="subhead">Radiographic findings</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="radFind" value="Caries"><span>Caries</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Bone loss"><span>Bone loss</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Periapical radiolucency"><span>Periapical radiolucency</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Impacted teeth"><span>Impacted teeth</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Calculus"><span>Calculus</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Root resorption"><span>Root resorption</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Cysts / lesions"><span>Cysts / lesions</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Sinus findings"><span>Sinus findings</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Implant assessment"><span>Implant assessment</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Fracture"><span>Fracture</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Retained root"><span>Retained root</span></label>
          <label class="chip"><input type="checkbox" name="radFind" value="Other"><span>Other</span></label>
        </div>
      </div>
      <div class="grid g2 span-full" style="margin-top:18px">
        <div class="field"><label class="flabel" for="imgCompare">Comparison with previous imaging</label><textarea id="imgCompare"></textarea></div>
        <div class="field"><label class="flabel" for="imgNotes">Radiology / imaging notes</label><textarea id="imgNotes"></textarea></div>
      </div>
    </div>
  </section>

  <!-- ===== 13. DIAGNOSIS ===== -->
  <section class="card" id="sec10">
    <div class="card-head"><div class="card-index">10</div><div><div class="card-title">Diagnosis</div><div class="card-desc">Structured selections &amp; site-specific diagnoses</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Diagnosis selections</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="dx" value="Dental caries"><span>Dental caries</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Reversible pulpitis"><span>Reversible pulpitis</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Irreversible pulpitis"><span>Irreversible pulpitis</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Pulp necrosis"><span>Pulp necrosis</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Symptomatic apical periodontitis"><span>Symptomatic apical periodontitis</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Asymptomatic apical periodontitis"><span>Asymptomatic apical periodontitis</span></label>
          <label class="chip danger"><input type="checkbox" name="dx" value="Acute apical abscess"><span>Acute apical abscess</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Chronic apical abscess"><span>Chronic apical abscess</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Cracked tooth"><span>Cracked tooth</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Fractured tooth"><span>Fractured tooth</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Fractured restoration"><span>Fractured restoration</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Failed restoration"><span>Failed restoration</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Periodontal disease"><span>Periodontal disease</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Gingivitis"><span>Gingivitis</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Pericoronitis"><span>Pericoronitis</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Impacted tooth"><span>Impacted tooth</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Malocclusion"><span>Malocclusion</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Bruxism"><span>Bruxism</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="TMJ disorder"><span>TMJ disorder</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Edentulism"><span>Edentulism</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Denture stomatitis"><span>Denture stomatitis</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Implant complication"><span>Implant complication</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Peri-implantitis"><span>Peri-implantitis</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Oral soft tissue lesion"><span>Oral soft tissue lesion</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Oral infection"><span>Oral infection</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Oral ulceration"><span>Oral ulceration</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Cosmetic concern"><span>Cosmetic concern</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Dental trauma"><span>Dental trauma</span></label>
          <label class="chip"><input type="checkbox" name="dx" value="Other"><span>Other</span></label>
        </div>
      </div>
      <div class="grid g2 span-full" style="margin-top:18px">
        <div class="field"><label class="flabel" for="dxDefinitive">Definitive diagnosis</label><textarea id="dxDefinitive"></textarea></div>
        <div class="field"><label class="flabel" for="dxDifferential">Differential diagnosis</label><textarea id="dxDifferential"></textarea></div>
      </div>
      <div class="field span-full" style="margin-top:20px"><span class="subhead">Tooth / site-specific diagnosis</span>
        <div class="tbl-wrap">
          <table class="data" id="dxTable">
            <thead><tr><th style="width:14%">Tooth / site</th><th style="width:28%">Diagnosis</th><th style="width:16%">Severity</th><th style="width:16%">Prognosis</th><th>Notes</th><th style="width:42px"></th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <button type="button" class="btn add-row" onclick="addDxRow()">+ Add diagnosis row</button>
      </div>
    </div>
  </section>

  <!-- ===== 14. TREATMENT PLAN ===== -->
  <section class="card" id="sec11">
    <div class="card-head"><div class="card-index">11</div><div><div class="card-title">Treatment Plan</div><div class="card-desc">Options, prognosis &amp; tooth-specific planning (XCD)</div></div></div>
    <div class="card-body">
      <div class="grid g2">
        <div class="field"><span class="subhead">Urgent treatment required</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="urgentTx" value="No" checked><span>No</span></label>
            <label class="radio alert"><input type="radio" name="urgentTx" value="Yes"><span>Yes</span></label>
          </div>
        </div>
        <div class="field"><label class="flabel" for="prognosis">Overall prognosis</label>
          <select id="prognosis"><option value="">Select…</option><option>Good</option><option>Fair</option><option>Guarded</option><option>Poor</option><option>Hopeless</option></select></div>
      </div>
      <div class="grid g2 span-full" style="margin-top:18px">
        <div class="field"><label class="flabel" for="txDiscussed">Treatment options discussed</label><textarea id="txDiscussed"></textarea></div>
        <div class="field"><label class="flabel" for="txRecommended">Recommended treatment</label><textarea id="txRecommended"></textarea></div>
        <div class="field"><label class="flabel" for="txAlternative">Alternative treatment</label><textarea id="txAlternative"></textarea></div>
        <div class="field"><label class="flabel" for="txNoTreatment">No-treatment option discussed</label><textarea id="txNoTreatment"></textarea></div>
      </div>
      <div class="field span-full" style="margin-top:8px">
        <label class="chip" style="display:inline-block"><input type="checkbox" id="txRisksExplained"><span>Risks &amp; benefits explained</span></label>
      </div>
      <div class="field span-full" style="margin-top:18px"><span class="subhead">Treatment categories</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="txCat" value="Preventive care"><span>Preventive care</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Oral hygiene instruction"><span>OHI</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Scaling / prophylaxis"><span>Scaling / prophylaxis</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Periodontal therapy"><span>Periodontal therapy</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Fillings / restorations"><span>Fillings / restorations</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Root canal treatment"><span>Root canal</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Crown / onlay / inlay"><span>Crown / onlay / inlay</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Bridge"><span>Bridge</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Complete denture"><span>Complete denture</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Partial denture"><span>Partial denture</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Denture repair"><span>Denture repair</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Implant"><span>Implant</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Extraction"><span>Extraction</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Surgical extraction"><span>Surgical extraction</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Oral surgery"><span>Oral surgery</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Orthodontics"><span>Orthodontics</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Whitening"><span>Whitening</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Veneers / cosmetic"><span>Veneers / cosmetic</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Night guard"><span>Night guard</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Pediatric preventive care"><span>Pediatric preventive</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Pulpotomy / pulpectomy"><span>Pulpotomy / pulpectomy</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Stainless steel crown"><span>Stainless steel crown</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Space maintainer"><span>Space maintainer</span></label>
          <label class="chip"><input type="checkbox" name="txCat" value="Referral"><span>Referral</span></label>
        </div>
      </div>
      <div class="field span-full" style="margin-top:20px"><span class="subhead">Tooth-specific treatment plan</span>
        <div class="tbl-wrap">
          <table class="data" id="txTable">
            <thead><tr><th style="width:10%">Tooth</th><th style="width:22%">Diagnosis</th><th style="width:26%">Recommended procedure</th><th style="width:13%">Priority</th><th style="width:15%">Est. cost (XCD)</th><th>Notes</th><th style="width:42px"></th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <button type="button" class="btn add-row" onclick="addTxRow()">+ Add treatment row</button>
      </div>
    </div>
  </section>

  <!-- ===== 15. PROCEDURE TODAY ===== -->
  <section class="card" id="sec12">
    <div class="card-head"><div class="card-index">12</div><div><div class="card-title">Procedure Performed Today</div><div class="card-desc">Treatment delivered this visit</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Procedure(s) performed</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="proc" value="No treatment performed"><span>No treatment</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Examination only"><span>Examination only</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Cleaning / prophylaxis"><span>Cleaning / prophylaxis</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Scaling / root planing"><span>Scaling / root planing</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Fluoride"><span>Fluoride</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Sealants"><span>Sealants</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Filling / restoration"><span>Filling / restoration</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Temporary filling"><span>Temporary filling</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Extraction"><span>Extraction</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Surgical extraction"><span>Surgical extraction</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Incision and drainage"><span>Incision &amp; drainage</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Root canal treatment"><span>Root canal</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Pulpotomy"><span>Pulpotomy</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Pulpectomy"><span>Pulpectomy</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Stainless steel crown"><span>Stainless steel crown</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Crown preparation"><span>Crown preparation</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Crown cementation"><span>Crown cementation</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Impression / scan"><span>Impression / scan</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Denture adjustment"><span>Denture adjustment</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Denture repair"><span>Denture repair</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Implant procedure"><span>Implant procedure</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Suture removal"><span>Suture removal</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Emergency treatment"><span>Emergency treatment</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Prescription only"><span>Prescription only</span></label>
          <label class="chip"><input type="checkbox" name="proc" value="Other"><span>Other</span></label>
        </div>
      </div>
      <div class="grid g4" style="margin-top:20px">
        <div class="field"><label class="flabel" for="procSite">Tooth / site</label><input type="text" id="procSite"></div>
        <div class="field"><label class="flabel" for="procAnesthetic">Anesthetic used</label><select id="procAnesthetic"><option value="">Select…</option><option>Lidocaine 2% with epinephrine 1:100,000</option><option>Lidocaine 2% with epinephrine 1:50,000</option><option>Articaine 4% with epinephrine 1:100,000</option><option>Articaine 4% with epinephrine 1:200,000</option><option>Mepivacaine 3% plain</option><option>Mepivacaine 2% with levonordefrin 1:20,000</option><option>Prilocaine 4% plain</option><option>Prilocaine 4% with epinephrine 1:200,000</option><option>Bupivacaine 0.5% with epinephrine 1:200,000</option><option>Topical benzocaine 20%</option><option>Topical lidocaine</option><option>Nitrous oxide/oxygen sedation</option><option>No local anesthetic used</option></select></div>
        <div class="field"><label class="flabel" for="procCarpules">Number of carpules</label><input type="number" step="0.5" id="procCarpules"></div>
        <div class="field"><label class="flabel" for="procNeedle">Needle type</label><select id="procNeedle"><option value="">Select…</option><option>25-gauge long needle</option><option>25-gauge short needle</option><option>27-gauge long needle</option><option>27-gauge short needle</option><option>30-gauge short needle</option><option>30-gauge extra-short needle</option><option>No needle used</option></select></div>
        <div class="field"><label class="flabel" for="procIsolation">Isolation method</label>
          <select id="procIsolation"><option value="">Select…</option><option>Cotton rolls</option><option>Rubber dam</option><option>Isolite / dry shield</option><option>High-volume suction</option><option>None</option></select></div>
        <div class="field span2"><label class="flabel" for="procMaterials">Materials used</label><input type="text" id="procMaterials" placeholder="e.g. composite shade A2, GIC…"></div>
        <div class="field"><span class="subhead">Tolerated well</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="procTolerated" value="Yes"><span>Yes</span></label>
            <label class="radio warn"><input type="radio" name="procTolerated" value="No"><span>No</span></label>
            <label class="radio"><input type="radio" name="procTolerated" value="N/A"><span>N/A</span></label>
          </div>
        </div>
      </div>
      <div class="grid g2 span-full" style="margin-top:18px">
        <div class="field"><label class="flabel" for="procNotes">Procedure notes</label><textarea id="procNotes"></textarea></div>
        <div class="field"><label class="flabel" for="procComplications">Complications</label><textarea id="procComplications" placeholder="None / describe"></textarea></div>
      </div>
    </div>
  </section>

  <!-- ===== 16. ANESTHESIA ===== -->
  <section class="card" id="sec13">
    <div class="card-head"><div class="card-index">13</div><div><div class="card-title">Anesthesia &amp; Sedation</div><div class="card-desc">Agents, dosing &amp; monitoring</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Technique used</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="anesTech" value="No anesthesia used"><span>None</span></label>
          <label class="chip"><input type="checkbox" name="anesTech" value="Topical anesthesia"><span>Topical</span></label>
          <label class="chip"><input type="checkbox" name="anesTech" value="Local infiltration"><span>Local infiltration</span></label>
          <label class="chip"><input type="checkbox" name="anesTech" value="Nerve block"><span>Nerve block</span></label>
          <label class="chip"><input type="checkbox" name="anesTech" value="Nitrous oxide"><span>Nitrous oxide</span></label>
          <label class="chip"><input type="checkbox" name="anesTech" value="Oral sedation"><span>Oral sedation</span></label>
          <label class="chip"><input type="checkbox" name="anesTech" value="IV sedation"><span>IV sedation</span></label>
          <label class="chip"><input type="checkbox" name="anesTech" value="General anesthesia referral"><span>GA referral</span></label>
        </div>
      </div>
      <div class="grid g4" style="margin-top:20px">
        <div class="field"><label class="flabel" for="anesAgent">Anesthetic agent</label>
          <select id="anesAgent"><option value="">Select…</option><option>Lidocaine with epinephrine</option><option>Articaine with epinephrine</option><option>Mepivacaine</option><option>Bupivacaine</option><option>Other</option></select></div>
        <div class="field"><label class="flabel" for="anesCarpules">Number of carpules</label><input type="number" step="0.5" id="anesCarpules"></div>
        <div class="field"><label class="flabel" for="anesEpi">Epinephrine concentration</label>
          <select id="anesEpi"><option value="">Select…</option><option>1:50,000</option><option>1:80,000</option><option>1:100,000</option><option>1:200,000</option><option>None</option></select></div>
        <div class="field"><label class="flabel" for="anesBatch">Batch / lot number</label><input type="text" id="anesBatch" placeholder="Lot #"></div>
      </div>
      <div class="grid g2" style="margin-top:18px">
        <div class="field"><span class="subhead">Adverse reaction</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="anesAdverse" value="No"><span>No</span></label>
            <label class="radio alert"><input type="radio" name="anesAdverse" value="Yes"><span>Yes</span></label>
          </div>
          <label class="chip" style="display:inline-block;margin-top:10px"><input type="checkbox" id="anesPostInstr"><span>Post-anesthesia instructions given</span></label>
        </div>
        <div class="field"><label class="flabel" for="anesNotes">Sedation notes</label><textarea id="anesNotes"></textarea></div>
      </div>
    </div>
  </section>

  <!-- ===== 17. PRESCRIPTIONS ===== -->
  <section class="card" id="sec14">
    <div class="card-head"><div class="card-index">14</div><div><div class="card-title">Prescriptions</div><div class="card-desc">Medication documentation — enter prescription orders through OpenEMR prescribing</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Quick-pick dental medications</span>
        <div class="chips" id="rxQuickPick">
          <button type="button" class="btn" onclick="addRxRow('Amoxicillin','500 mg','TDS','5 days','15 caps','0')">Amoxicillin</button>
          <button type="button" class="btn" onclick="addRxRow('Clindamycin','300 mg','QDS','5 days','20 caps','0')">Clindamycin</button>
          <button type="button" class="btn" onclick="addRxRow('Metronidazole','400 mg','TDS','5 days','15 tabs','0')">Metronidazole</button>
          <button type="button" class="btn" onclick="addRxRow('Azithromycin','500 mg','OD','3 days','3 tabs','0')">Azithromycin</button>
          <button type="button" class="btn" onclick="addRxRow('Ibuprofen','400 mg','TDS','5 days','15 tabs','0')">Ibuprofen</button>
          <button type="button" class="btn" onclick="addRxRow('Paracetamol','1 g','QDS','5 days','20 tabs','0')">Paracetamol</button>
          <button type="button" class="btn" onclick="addRxRow('Chlorhexidine 0.2% mouthwash','10 mL','BD','7 days','1 bottle','0')">Chlorhexidine</button>
          <button type="button" class="btn" onclick="addRxRow('Topical fluoride','—','As directed','—','1 tube','0')">Topical fluoride</button>
          <button type="button" class="btn" onclick="addRxRow('','','','','','0')">Other</button>
        </div>
      </div>
      <div class="field span-full" style="margin-top:18px">
        <div class="tbl-wrap">
          <table class="data" id="rxTable">
            <thead><tr><th style="width:20%">Medication</th><th>Dose</th><th>Frequency</th><th>Duration</th><th>Quantity</th><th>Refills</th><th style="width:16%">Indication</th><th>Instructions</th><th style="width:42px"></th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <button type="button" class="btn add-row" onclick="addRxRow()">+ Add prescription</button>
        <button type="button" class="btn btn-accent" style="margin-top:12px;margin-left:8px" onclick="printRx()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;vertical-align:-2px;margin-right:6px"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg>
          Print medication instructions
        </button>
      </div>
      <div class="declaration" style="margin-top:16px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;flex:none"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
        <span><strong>Allergy check:</strong> Confirm the patient's allergy status (Section 4) before prescribing. Verify antibiotic prophylaxis requirements and renal/hepatic dosing where relevant.</span>
      </div>
    </div>
  </section>

  <!-- ===== 18. CONSENT & EDUCATION ===== -->
  <section class="card" id="sec15">
    <div class="card-head"><div class="card-index">15</div><div><div class="card-title">Patient Education, Consent &amp; Instructions</div><div class="card-desc">Documented discussion &amp; consent</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Documented &amp; discussed</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="consent" value="Diagnosis explained"><span>Diagnosis explained</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Treatment options explained"><span>Treatment options explained</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Risks and benefits explained"><span>Risks &amp; benefits explained</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Alternatives explained"><span>Alternatives explained</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="No treatment option explained"><span>No-treatment option explained</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Costs discussed"><span>Costs discussed</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Post-operative instructions given"><span>Post-op instructions given</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Oral hygiene instructions given"><span>OHI given</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Diet advice given"><span>Diet advice given</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Smoking cessation advice given"><span>Smoking cessation advice</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Consent obtained"><span>Consent obtained</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Patient questions answered"><span>Questions answered</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Written instructions provided"><span>Written instructions provided</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Referral letter provided"><span>Referral letter provided</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Medical clearance discussed"><span>Medical clearance discussed</span></label>
          <label class="chip"><input type="checkbox" name="consent" value="Emergency warning signs explained"><span>Emergency warning signs explained</span></label>
        </div>
      </div>
      <div class="grid g2 span-full" style="margin-top:18px">
        <div class="field"><label class="flabel" for="consentNotes">Consent notes</label><textarea id="consentNotes"></textarea></div>
        <div class="field"><label class="flabel" for="postOpNotes">Post-operative instruction notes</label><textarea id="postOpNotes"></textarea></div>
      </div>
    </div>
  </section>

  <!-- ===== 19. REFERRALS ===== -->
  <section class="card" id="sec16">
    <div class="card-head"><div class="card-index">16</div><div><div class="card-title">Referrals</div><div class="card-desc">Onward specialist or medical referral</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Refer to</span>
        <div class="chips">
          <label class="chip"><input type="checkbox" name="referral" value="No referral"><span>No referral</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Oral surgeon"><span>Oral surgeon</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Endodontist"><span>Endodontist</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Periodontist"><span>Periodontist</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Orthodontist"><span>Orthodontist</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Prosthodontist"><span>Prosthodontist</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Pediatric dentist"><span>Pediatric dentist</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Oral medicine / pathology"><span>Oral medicine / pathology</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Medical physician"><span>Medical physician</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Radiology / CBCT"><span>Radiology / CBCT</span></label>
          <label class="chip danger"><input type="checkbox" name="referral" value="Hospital / emergency department"><span>Hospital / ED</span></label>
          <label class="chip"><input type="checkbox" name="referral" value="Other"><span>Other</span></label>
        </div>
      </div>
      <div class="grid g3" style="margin-top:20px">
        <div class="field span2"><label class="flabel" for="referralReason">Reason for referral</label><input type="text" id="referralReason"></div>
        <div class="field"><span class="subhead">Urgency</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="referralUrgency" value="Routine"><span>Routine</span></label>
            <label class="radio warn"><input type="radio" name="referralUrgency" value="Urgent"><span>Urgent</span></label>
            <label class="radio alert"><input type="radio" name="referralUrgency" value="Emergency"><span>Emergency</span></label>
          </div>
        </div>
      </div>
      <div class="field span-full" style="margin-top:16px"><label class="flabel" for="referralNotes">Referral notes</label><textarea id="referralNotes"></textarea></div>
    </div>
  </section>

  <!-- ===== 20. FOLLOW-UP ===== -->
  <section class="card" id="sec17">
    <div class="card-head"><div class="card-index">17</div><div><div class="card-title">Follow-Up &amp; Recall Plan</div><div class="card-desc">Review timing &amp; recall scheduling</div></div></div>
    <div class="card-body">
      <div class="grid g4">
        <div class="field"><span class="subhead">Follow-up required</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="fuRequired" value="No"><span>No</span></label>
            <label class="radio"><input type="radio" name="fuRequired" value="Yes"><span>Yes</span></label>
          </div>
        </div>
        <div class="field"><label class="flabel" for="fuReview">Review in</label>
          <select id="fuReview"><option value="">Select…</option><option>24–48 hours</option><option>1 week</option><option>2 weeks</option><option>1 month</option><option>3 months</option><option>6 months</option><option>Other</option></select></div>
        <div class="field"><label class="flabel" for="fuType">Next appointment type</label>
          <select id="fuType"><option value="">Select…</option><option>Review</option><option>Hygiene</option><option>Restoration</option><option>Extraction</option><option>Root canal</option><option>Crown / bridge</option><option>Denture</option><option>Implant</option><option>Orthodontic review</option><option>Pediatric recall</option><option>Other</option></select></div>
        <div class="field"><label class="flabel" for="fuRecall">Recall interval</label>
          <select id="fuRecall"><option value="">Select…</option><option>3 months</option><option>6 months</option><option>12 months</option><option>Other</option></select></div>
      </div>
      <div class="grid g3 span-full" style="margin-top:18px">
        <div class="field"><label class="flabel" for="fuInvestigations">Pending investigations</label><input type="text" id="fuInvestigations"></div>
        <div class="field"><label class="flabel" for="fuPendingTx">Pending treatment</label><input type="text" id="fuPendingTx"></div>
        <div class="field"><label class="flabel" for="fuNotes">Follow-up notes</label><input type="text" id="fuNotes"></div>
      </div>
    </div>
  </section>

  <!-- ===== 21. BILLING XCD ===== -->
  <section class="card" id="sec18">
    <div class="card-head"><div class="card-index">18</div><div><div class="card-title">Billing, Insurance &amp; Payment</div><div class="card-desc">Encounter billing notes in XCD — charges and payments must also be entered in OpenEMR billing</div></div></div>
    <div class="card-body">
      <div class="field span-full"><span class="subhead">Procedures &amp; charges</span>
        <div class="tbl-wrap">
          <table class="data" id="billTable">
            <thead><tr><th style="width:14%">Code</th><th>Description</th><th style="width:18%">Amount (XCD)</th><th style="width:42px"></th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <button type="button" class="btn add-row" onclick="addBillRow()">+ Add line item</button>
      </div>

      <div class="grid g4" style="margin-top:22px">
        <div class="field"><label class="flabel" for="billDiscount">Discount (XCD)</label><input type="number" step="0.01" id="billDiscount" value="0" oninput="calcBilling()"></div>
        <div class="field"><label class="flabel" for="billTax">Tax / fees (XCD)</label><input type="number" step="0.01" id="billTax" value="0" oninput="calcBilling()"></div>
        <div class="field"><label class="flabel" for="billPaid">Amount paid (XCD)</label><input type="number" step="0.01" id="billPaid" value="0" oninput="calcBilling()"></div>
        <div class="field"><label class="flabel" for="billDeposit">Deposit required (XCD)</label><input type="number" step="0.01" id="billDeposit" value="0"></div>
      </div>

      <div class="grid g3" style="margin-top:8px;background:var(--teal-soft);border:1px solid var(--teal-line);border-radius:12px;padding:16px 18px;margin-top:20px">
        <div class="field"><span class="flabel">Subtotal</span><div style="font-family:var(--serif);font-size:20px;font-weight:600;color:var(--teal-deep)">XCD <span id="sumSubtotal">0.00</span></div></div>
        <div class="field"><span class="flabel">Total estimate</span><div style="font-family:var(--serif);font-size:20px;font-weight:600;color:var(--teal-deep)">XCD <span id="sumTotal">0.00</span></div></div>
        <div class="field"><span class="flabel">Balance due</span><div style="font-family:var(--serif);font-size:20px;font-weight:600;color:var(--danger)" id="balanceWrap">XCD <span id="sumBalance">0.00</span></div></div>
      </div>

      <div class="grid g3" style="margin-top:20px">
        <div class="field"><span class="subhead">Insurance involved</span>
          <div class="radios">
            <label class="radio"><input type="radio" name="insInvolved" value="No" checked><span>No</span></label>
            <label class="radio"><input type="radio" name="insInvolved" value="Yes"><span>Yes</span></label>
          </div>
        </div>
        <div class="field"><label class="flabel" for="insPreauth">Preauthorization status</label>
          <select id="insPreauth"><option value="">N/A</option><option>Not required</option><option>Required</option><option>Submitted</option><option>Approved</option><option>Denied</option></select></div>
        <div class="field"><label class="flabel" for="payMethod">Payment method</label>
          <select id="payMethod"><option value="">Select…</option><option>Cash</option><option>Card</option><option>Bank transfer</option><option>Insurance</option><option>Mixed payment</option><option>Other</option></select></div>
      </div>
      <div class="grid g2 span-full" style="margin-top:18px">
        <div class="field"><label class="flabel" for="insNotes">Insurance notes</label><textarea id="insNotes"></textarea></div>
        <div class="field"><label class="flabel" for="finNotes">Financial notes</label><textarea id="finNotes"></textarea></div>
      </div>
      <label class="chip" style="display:inline-block;margin-top:14px"><input type="checkbox" id="finConsent"><span>Financial consent obtained</span></label>
    </div>
  </section>

  <!-- ===== 22. SIGN-OFF ===== -->
  <section class="card" id="sec19">
    <div class="card-head"><div class="card-index">19</div><div><div class="card-title">Provider Sign-Off</div><div class="card-desc">Documentation and signature image — use OpenEMR e-sign to sign and lock the record</div></div></div>
    <div class="card-body">
      <div class="grid g3">
        <div class="field"><label class="flabel" for="signProvider">Dentist / provider name</label><input type="text" id="signProvider"></div>
        <div class="field"><label class="flabel" for="signAssistant">Hygienist / assistant name</label><input type="text" id="signAssistant"></div>
        <div class="field"><label class="flabel" for="signDateTime">Date &amp; time completed</label><input type="datetime-local" id="signDateTime"></div>
      </div>
      <div class="field span-full" style="margin-top:18px"><label class="flabel" for="finalNotes">Final clinical notes</label><textarea id="finalNotes" style="min-height:90px"></textarea></div>

      <div class="field span-full" style="margin-top:18px">
        <label class="flabel" for="signaturePad">Signature image</label>
        <canvas id="signaturePad" class="sign-pad" aria-label="Signature pad"></canvas>
        <button type="button" class="btn btn-ghost" style="margin-top:8px;align-self:flex-start" onclick="clearSignature()">Clear signature</button>
      </div>

      <div class="declaration" style="margin-top:18px">
        <input type="checkbox" id="declaration" aria-describedby="declText">
        <span id="declText">I confirm that the examination, findings, diagnosis, treatment plan, consent discussion, and procedure notes recorded in this encounter have been documented accurately and completely.</span>
      </div>

      <div class="foot-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg> Print / Export PDF
        </button>
        <button type="button" class="btn" onclick="saveForm()" data-save>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg> Save encounter
        </button>
        <button type="button" class="btn btn-danger" onclick="resetForm()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg> Reset form
        </button>
      </div>
    </div>
  </section>

  </form>
</div>

<!-- Tooth editor modal -->
<div class="modal-backdrop" id="toothModal" role="dialog" aria-modal="true" aria-labelledby="modalToothLabel">
  <div class="modal">
    <div class="modal-head">
      <div class="m-tooth" id="modalToothId">—</div>
      <div><h3 id="modalToothLabel">Tooth findings</h3><p id="modalToothSub">Select findings, surfaces &amp; notes</p></div>
      <button type="button" class="modal-close" onclick="closeToothModal()" aria-label="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <span class="subhead">Findings</span>
      <div class="chips" id="modalFindings"></div>
      <div id="surfaceWrap" style="margin-top:18px">
        <span class="subhead">Surfaces involved</span>
        <div class="chips" id="modalSurfaces"></div>
      </div>
      <div class="field" style="margin-top:18px"><label class="flabel" for="modalNotes">Tooth-specific notes</label><textarea id="modalNotes"></textarea></div>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-danger" onclick="clearTooth()">Clear tooth</button>
      <button type="button" class="btn btn-primary" onclick="saveTooth()">Save findings</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span id="toastMsg">Saved</span></div>

