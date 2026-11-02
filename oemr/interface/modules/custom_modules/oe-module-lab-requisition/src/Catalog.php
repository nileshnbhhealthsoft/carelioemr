<?php

namespace OpenEMR\Modules\LabRequisition;

/**
 * The tick-boxes on the paper requisition, in paper order. This is the only list of
 * orderable items: the form is drawn from it and the saved values are checked against it.
 *
 * An item is a label, or [label, true] when the paper form has a "state" line beside it.
 * Codes are derived as <section>_<slug of label> and are what gets stored.
 */
class Catalog
{
    public const SPECIMEN_SECTION = 'specimen';

    /** @return array<string,array{title:string,layout:string,cols:array<int,array{flex:float,items:array}>}> */
    public static function sections(): array
    {
        static $built = null;
        if ($built !== null) {
            return $built;
        }
        $def = [
            'panels' => ['Panels / Profiles', 'grid', [[1, [
                'Renal function', 'Liver function', 'Thyroid function', 'Lipid Profile', 'Cardiac Enzymes', 'S.O.B. - Chest Pain Panel',
            ]]]],
            'chem' => ['Chemistry', 'cols', [
                [1, ['BUN (urea)', 'creatinine', 'Electrolytes (Na/K/Cl)', 'carbon dioxide']],
                [1, ['alk phos.', 'bilirubin (T&D)', 'ALT (SGPT)', 'AST (SGOT)', 'GGT']],
                [1, ['protein', 'albumin', 'globulin', 'creatine kinase', 'LDH']],
                [1, ['cholesterol', 'HDL/LDL chol', 'triglycerides', 'Coronary risk']],
                [1, ['glucose', 'glycohemoglobin', 'Amylase', 'uric acid']],
                [1, ['calcium', 'Ionised Calcium', 'phosphorus', 'magnesium', 'lipase']],
            ]],
            'heme' => ['Hematology', 'cols', [
                [1, ['CBC', 'CBC with Film', 'Blood Group']],
                [1, ['HB electrophoresis/sickle', 'Retic Count', 'ESR']],
                [1, ['Vitamin B12/Folate', 'Ferritin', 'Fe-UIBC/TIBC']],
                [1, ['PT', 'PTT', 'Bleeding Time']],
                [1, ['IDCT', 'DCT', 'D-Dimer']],
                [1, ['C. Reactive Protein', 'Dengue', ['Other', true]]],
            ]],
            'immun' => ['Immunology / Serology', 'cols', [
                [1, ['RF', 'ANF/ANA', 'anti-DNA']],
                [1, ['Protein Electrophoresis', 'beta-2-Microglobulin']],
                [1, ['ASOT', 'WIDAL', 'C3', 'HEPC']],
                [1, ['HIV Ab', 'HTLV-1 Ab', 'HBsAG', 'anti-HBs', ['leptospira Ab Ig', true]]],
                [1, [['HSV Ab Ig', true], 'toxoplas Ab Ig', 'RPR (VDRL)', 'TPHA', 'Mononucleosis']],
                [1, ['Rubella Ab', ['CMV Ab Ig', true], 'H-Pylori', 'Pregnancy', 'Paternity']],
            ]],
            'horm' => ['Hormones', 'cols', [
                [1, ['TSH', 'Free T4', 'Free T3']],
                [1, ['FSH', 'LH', 'Prolactin']],
                [1, ['Testosterone', 'Estradiol', 'Progesterone']],
                [1, ['Cortisol', 'ACTH', 'HS-CRP']],
                [1.6, ['Urine VMA', 'Urine Microprotein', 'Urine Microalbumin', 'Creatine Clearance']],
            ]],
            'cancer' => ['Cancer', 'grid', [[1, [
                'PSA', 'PAP', 'CEA', 'CA-125', 'CA-15.3', 'AFP', 'Beta-HCG', 'CA-19.9', ['Other (state)', true],
            ]]]],
            // The paper form lists Opiates twice (columns 1 and 5); it is kept once, in column 1.
            'drug' => ['Drugs', 'cols', [
                [1, ['Digoxin', 'Opiates']],
                [1, ['Phenobarbital', 'Carbamazepine']],
                [1, ['Theophylline (Aminophylline)', 'Valproic Acid']],
                [1, ['Alcohol', 'Nicotine']],
                [1, ['Cocaine']],
                [1, ['Marijuana (cannabinoids)']],
            ]],
            'micro' => ['Microbiology', 'cols', [
                [1.4, [['Culture + sensitivity on', true], 'Gram Stain', 'GC Smear']],
                [1, ['OCP', 'occult blood', 'Chlamydia Ag', 'HSV Ag']],
                [1.4, ['urinalysis/microscopy', 'acid fast stain', ['Other (state)', true]]],
                [1, ['Semen analysis']],
            ]],
        ];

        $built = [];
        foreach ($def as $key => [$title, $layout, $cols]) {
            $built[$key] = ['title' => $title, 'layout' => $layout, 'cols' => []];
            foreach ($cols as [$flex, $items]) {
                $out = [];
                foreach ($items as $it) {
                    $label = is_array($it) ? $it[0] : $it;
                    $out[] = ['code' => $key . '_' . self::slug($label), 'label' => $label, 'detail' => is_array($it)];
                }
                $built[$key]['cols'][] = ['flex' => (float)$flex, 'items' => $out];
            }
        }
        return $built;
    }

    /** Specimen tick-boxes at the top of the form. */
    public static function specimens(): array
    {
        return [
            ['code' => 'urine', 'label' => 'Urine', 'detail' => false],
            ['code' => 'blood', 'label' => 'Blood', 'detail' => false],
            ['code' => 'stool', 'label' => 'Stool', 'detail' => false],
            ['code' => 'swab', 'label' => 'Swab (state)', 'detail' => true],
            ['code' => 'other', 'label' => 'Other', 'detail' => true],
            ['code' => 'bone_marrow', 'label' => 'Bone marrow', 'detail' => false],
            ['code' => 'cytology', 'label' => 'cytology (state site/fix/no fix)', 'detail' => true],
        ];
    }

    /** @return array<string,array{label:string,detail:bool,section:string}> test code => info */
    public static function tests(): array
    {
        $map = [];
        foreach (self::sections() as $key => $sec) {
            foreach ($sec['cols'] as $col) {
                foreach ($col['items'] as $it) {
                    $map[$it['code']] = ['label' => $it['label'], 'detail' => $it['detail'], 'section' => $sec['title']];
                }
            }
        }
        return $map;
    }

    /** @return array<string,array{label:string,detail:bool}> specimen code => info */
    public static function specimenMap(): array
    {
        $map = [];
        foreach (self::specimens() as $s) {
            $map[$s['code']] = ['label' => $s['label'], 'detail' => $s['detail']];
        }
        return $map;
    }

    private static function slug(string $label): string
    {
        return trim((string)preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');
    }
}
