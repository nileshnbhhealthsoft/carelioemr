<?php

namespace OpenEMR\Modules\RadiologyRequisition;

/**
 * Everything selectable on the requisition: modalities, their examinations and their safety
 * screening questions. The form is drawn from it and saved values are checked against it.
 * Safety answers are stored against the question text, so reordering questions here is safe.
 */
class Catalog
{
    public const LATERALITY = ['Not applicable', 'Right', 'Left', 'Bilateral'];
    public const CONTRAST = [
        'Not applicable', 'Without contrast', 'With IV contrast', 'With and without IV contrast',
        'Oral contrast', 'IV and oral contrast', 'Radiologist to determine',
    ];
    public const PRIORITY = ['Routine', 'Urgent', 'STAT / Emergency'];
    public const SETTING = ['Outpatient', 'Inpatient', 'Emergency department'];
    public const SEX = ['Female', 'Male', 'Intersex', 'Unknown'];
    public const ANSWERS = [
        'unknown' => 'Unknown / Not assessed',
        'no' => 'No — confirmed',
        'yes' => 'Yes — confirmed',
        'na' => 'Not applicable',
    ];
    /** Modalities where the radiologist normally decides contrast, and the renal-results block applies. */
    public const CONTRAST_DEFAULT = ['CT', 'MRI', 'Fluoroscopy', 'Nuclear Medicine'];
    public const RENAL_BLOCK = ['CT', 'MRI', 'Nuclear Medicine'];
    public const MAX_STUDIES = 20;

    public static function exams(): array
    {
        return [
            'X-ray' => ['Chest', 'Abdomen', 'Pelvis', 'Cervical spine', 'Thoracic spine', 'Lumbar spine', 'Shoulder', 'Humerus', 'Elbow', 'Forearm', 'Wrist', 'Hand', 'Hip', 'Femur', 'Knee', 'Tibia/Fibula', 'Ankle', 'Foot', 'Ribs', 'Skull', 'Sinuses', 'Other'],
            'CT' => ['Brain / Head', 'Sinuses', 'Neck', 'Chest', 'CT Pulmonary Angiography (CTPA)', 'Abdomen', 'Pelvis', 'Abdomen and Pelvis', 'CT KUB / Renal stone protocol', 'Cervical spine', 'Thoracic spine', 'Lumbar spine', 'CT Angiography', 'CT Colonography', 'Other'],
            'MRI' => ['Brain', 'Pituitary', 'Orbits', 'Internal auditory canals', 'Cervical spine', 'Thoracic spine', 'Lumbar spine', 'Neck', 'Shoulder', 'Knee', 'Hip', 'Pelvis', 'Abdomen', 'Liver', 'MRCP', 'Breast', 'MRA', 'Other'],
            'Ultrasound' => ['Abdomen', 'Pelvis', 'Transvaginal pelvis', 'Obstetric', 'Renal tract', 'Thyroid', 'Breast', 'Scrotum / Testes', 'Soft tissue', 'Doppler venous', 'Doppler arterial', 'Carotid Doppler', 'Echocardiogram', 'Other'],
            'Mammography' => ['Bilateral screening', 'Bilateral diagnostic', 'Right diagnostic', 'Left diagnostic', 'Tomosynthesis', 'Other'],
            'Fluoroscopy' => ['Barium swallow', 'Upper GI series', 'Small bowel follow-through', 'Barium enema', 'Hysterosalpingogram', 'Cystogram', 'Voiding cystourethrogram', 'Other'],
            'Nuclear Medicine' => ['Bone scan', 'Thyroid scan', 'Renal scan', 'V/Q scan', 'HIDA scan', 'Myocardial perfusion', 'PET/CT', 'Other'],
            'Other' => ['Other'],
        ];
    }

    /** @return array<string,string[]> modality => ordered question texts */
    public static function safety(): array
    {
        $preg = 'Pregnant or possibly pregnant? (if applicable)';
        return [
            'CT' => ['Previous iodinated contrast reaction?', 'Known kidney disease or acute kidney injury?', $preg, 'Taking metformin?', 'History of thyroid disease relevant to contrast?'],
            'MRI' => ['Pacemaker, ICD or implanted electronic device?', 'Aneurysm clip, metallic implant or foreign body?', 'Previous metal injury to the eye?', $preg, 'Previous gadolinium contrast reaction?', 'Known kidney disease or acute kidney injury?', 'Claustrophobia or sedation needs?'],
            'Nuclear Medicine' => [$preg, 'Currently breastfeeding? (if applicable)', 'Recent nuclear medicine study or radiopharmaceutical?', 'Recent iodinated contrast or thyroid medication?'],
            'X-ray' => [$preg],
            'Fluoroscopy' => [$preg, 'Previous iodinated contrast reaction? (if applicable)'],
            'Mammography' => [$preg, 'Breast implants?'],
            'Ultrasound' => ['Pregnancy status relevant to this examination?'],
            'Other' => [],
        ];
    }

    public static function defaultContrast(string $modality): string
    {
        return in_array($modality, self::CONTRAST_DEFAULT, true) ? 'Radiologist to determine' : 'Not applicable';
    }

    /** Everything the browser-side study builder needs. */
    public static function forBrowser(): array
    {
        return [
            'exams' => self::exams(), 'safety' => self::safety(), 'answers' => self::ANSWERS,
            'laterality' => self::LATERALITY, 'contrast' => self::CONTRAST,
            'contrastDefault' => self::CONTRAST_DEFAULT, 'renal' => self::RENAL_BLOCK,
        ];
    }
}
