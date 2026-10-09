<?php

namespace OpenEMR\Modules\ClaimForms;

/**
 * Common ICD-10-CM codes for optometry and ophthalmology, so the section 8 diagnosis picker has
 * suggestions even on a site that has not loaded the full ICD-10 code set. Descriptions are short
 * labels for choosing; only the code is ever stored or printed.
 */
class EyeCodes
{
    private const LIST = [
        'H52.00' => 'Hypermetropia, unspecified eye', 'H52.01' => 'Hypermetropia, right eye', 'H52.02' => 'Hypermetropia, left eye', 'H52.03' => 'Hypermetropia, bilateral',
        'H52.10' => 'Myopia, unspecified eye', 'H52.11' => 'Myopia, right eye', 'H52.12' => 'Myopia, left eye', 'H52.13' => 'Myopia, bilateral',
        'H52.201' => 'Unspecified astigmatism, right eye', 'H52.202' => 'Unspecified astigmatism, left eye', 'H52.203' => 'Unspecified astigmatism, bilateral',
        'H52.211' => 'Irregular astigmatism, right eye', 'H52.212' => 'Irregular astigmatism, left eye', 'H52.213' => 'Irregular astigmatism, bilateral',
        'H52.4' => 'Presbyopia', 'H52.31' => 'Anisometropia', 'H52.32' => 'Aniseikonia', 'H52.7' => 'Unspecified disorder of refraction',
        'H53.2' => 'Diplopia', 'H53.40' => 'Unspecified visual field defects', 'H53.8' => 'Other visual disturbances', 'H53.9' => 'Unspecified visual disturbance',
        'H54.7' => 'Unspecified visual loss', 'H54.0' => 'Blindness, both eyes',
        'H10.9' => 'Unspecified conjunctivitis', 'H10.10' => 'Acute atopic conjunctivitis, unspecified eye', 'H10.30' => 'Unspecified acute conjunctivitis, unspecified eye',
        'H10.45' => 'Other chronic allergic conjunctivitis', 'H11.30' => 'Conjunctival hemorrhage, unspecified eye',
        'H04.123' => 'Dry eye syndrome of bilateral lacrimal glands', 'H04.129' => 'Dry eye syndrome of unspecified lacrimal gland',
        'H16.9' => 'Unspecified keratitis', 'H18.60' => 'Keratoconus, unspecified', 'H18.601' => 'Keratoconus, unspecified, right eye', 'H18.602' => 'Keratoconus, unspecified, left eye',
        'H25.9' => 'Unspecified age-related cataract', 'H25.10' => 'Age-related nuclear cataract, unspecified eye', 'H26.9' => 'Unspecified cataract',
        'H40.10X0' => 'Unspecified open-angle glaucoma, stage unspecified', 'H40.9' => 'Unspecified glaucoma', 'H40.003' => 'Preglaucoma, unspecified, bilateral',
        'H35.30' => 'Unspecified macular degeneration', 'H35.3190' => 'Nonexudative age-related macular degeneration, unspecified eye',
        'H33.009' => 'Unspecified retinal detachment with retinal break, unspecified eye', 'H35.9' => 'Unspecified retinal disorder',
        'E11.9' => 'Type 2 diabetes mellitus without complications', 'E11.319' => 'Type 2 diabetes with unspecified diabetic retinopathy without macular edema',
        'E11.329' => 'Type 2 diabetes with mild nonproliferative diabetic retinopathy without macular edema',
        'H57.10' => 'Ocular pain, unspecified eye', 'H57.9' => 'Unspecified disorder of eye and adnexa',
        'H00.019' => 'Hordeolum externum, unspecified eye, unspecified eyelid', 'H00.10' => 'Chalazion, unspecified eye',
        'H01.009' => 'Unspecified blepharitis, unspecified eye', 'H02.409' => 'Unspecified ptosis of unspecified eyelid',
        'H50.9' => 'Unspecified strabismus', 'H55.00' => 'Unspecified nystagmus', 'H46.9' => 'Unspecified optic neuritis',
        'H47.10' => 'Unspecified papilledema', 'H43.399' => 'Other vitreous opacities, unspecified eye', 'H43.10' => 'Vitreous hemorrhage, unspecified eye',
        'H11.00' => 'Unspecified pterygium of eye', 'H11.001' => 'Unspecified pterygium of right eye', 'H11.002' => 'Unspecified pterygium of left eye',
        'S05.00XA' => 'Injury of conjunctiva and corneal abrasion without foreign body, unspecified eye, initial', 'T15.90XA' => 'Foreign body on external eye, unspecified, initial',
        'Z01.00' => 'Encounter for examination of eyes and vision without abnormal findings', 'Z01.01' => 'Encounter for examination of eyes and vision with abnormal findings',
        'Z46.0' => 'Encounter for fitting and adjustment of spectacles and contact lenses', 'Z97.3' => 'Presence of spectacles and contact lenses',
        'Z96.1' => 'Presence of intraocular lens', 'Z83.511' => 'Family history of glaucoma',
    ];

    /** @return array<int,array{code:string,label:string,text:string}> codes whose code or text contains $q (all of them when $q is empty) */
    public static function search(string $q): array
    {
        $q = strtolower(trim($q));
        $out = [];
        foreach (self::LIST as $code => $text) {
            if ($q === '' || str_contains(strtolower($code), $q) || str_contains(strtolower($text), $q)) {
                $out[] = ['code' => $code, 'label' => $code, 'text' => $text];
            }
        }
        return $out;
    }
}
