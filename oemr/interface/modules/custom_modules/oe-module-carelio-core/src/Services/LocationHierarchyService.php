<?php

namespace OpenEMR\Modules\CarelioCore\Services;

class LocationHierarchyService
{
    /**
     * Get complete hierarchy tree for a facility
     */
    public static function getTree(int $facilityId = 1): array
    {
        $sql = "SELECT * FROM `hms_location` WHERE `facility_id` = ? ORDER BY `sort_order` ASC, `name` ASC";
        $rows = sqlStatement($sql, [$facilityId]);
        $locations = [];
        while ($row = sqlFetchArray($rows)) {
            $locations[] = $row;
        }

        return self::buildTree($locations);
    }

    /**
     * Recursive tree builder
     */
    private static function buildTree(array $elements, $parentId = null): array
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = self::buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                } else {
                    $element['children'] = [];
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    /**
     * Get parent options (all non-bed locations) for dropdowns
     */
    public static function getParentOptions(int $facilityId = 1): array
    {
        $sql = "SELECT id, name, code, location_type FROM `hms_location` 
                WHERE `facility_id` = ? AND `location_type` != 'bed' 
                ORDER BY FIELD(location_type, 'site', 'building', 'floor', 'ward', 'room'), name ASC";
        $rows = sqlStatement($sql, [$facilityId]);
        $options = [];
        while ($row = sqlFetchArray($rows)) {
            $options[] = $row;
        }
        return $options;
    }

    /**
     * Add a new location node
     */
    public static function addLocation(array $data): int
    {
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $facilityId = !empty($data['facility_id']) ? (int)$data['facility_id'] : 1;
        $type = $data['location_type'];
        $code = trim($data['code']);
        $name = trim($data['name']);
        $specialty = !empty($data['specialty']) ? trim($data['specialty']) : null;
        $careLevel = !empty($data['care_level']) ? trim($data['care_level']) : null;
        $accommodation = !empty($data['accommodation_type']) ? trim($data['accommodation_type']) : null;
        $status = !empty($data['status']) ? trim($data['status']) : 'active';
        $sortOrder = !empty($data['sort_order']) ? (int)$data['sort_order'] : 0;

        $sql = "INSERT INTO `hms_location` 
                (`parent_id`, `facility_id`, `location_type`, `code`, `name`, `specialty`, `care_level`, `accommodation_type`, `status`, `sort_order`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return sqlInsert($sql, [$parentId, $facilityId, $type, $code, $name, $specialty, $careLevel, $accommodation, $status, $sortOrder]);
    }

    /**
     * Update an existing location node
     */
    public static function updateLocation(int $id, array $data): bool
    {
        $sql = "UPDATE `hms_location` SET 
                `name` = ?, 
                `code` = ?, 
                `specialty` = ?, 
                `care_level` = ?, 
                `accommodation_type` = ?, 
                `status` = ?
                WHERE `id` = ?";
        sqlStatement($sql, [
            trim($data['name']),
            trim($data['code']),
            !empty($data['specialty']) ? trim($data['specialty']) : null,
            !empty($data['care_level']) ? trim($data['care_level']) : null,
            !empty($data['accommodation_type']) ? trim($data['accommodation_type']) : null,
            !empty($data['status']) ? trim($data['status']) : 'active',
            $id
        ]);
        return true;
    }

    /**
     * Delete a location node (fails if children exist)
     */
    public static function deleteLocation(int $id): bool
    {
        $check = sqlQuery("SELECT COUNT(*) as cnt FROM `hms_location` WHERE `parent_id` = ?", [$id]);
        if ($check['cnt'] > 0) {
            throw new \Exception("Cannot delete location with child rooms or beds. Delete children first.");
        }
        sqlStatement("DELETE FROM `hms_location` WHERE `id` = ?", [$id]);
        return true;
    }

    /**
     * Seed Default Saint Lucia Hospital Layout (Victoria / OKEU Hospital Model)
     */
    public static function seedSaintLuciaHospital(int $facilityId = 1): void
    {
        // Check if locations already exist
        $existing = sqlQuery("SELECT COUNT(*) as cnt FROM `hms_location` WHERE `facility_id` = ?", [$facilityId]);
        if ($existing['cnt'] > 0) {
            return;
        }

        // 1. Site
        $siteId = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => null,
            'location_type' => 'site',
            'code' => 'SLU-OKEU-01',
            'name' => 'OKEU Hospital Main Campus (Saint Lucia)',
            'status' => 'active'
        ]);

        // 2. Main Clinical Block
        $buildingId = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $siteId,
            'location_type' => 'building',
            'code' => 'BLD-CLIN-01',
            'name' => 'Inpatient Clinical Tower',
            'status' => 'active'
        ]);

        // 3. Ground Floor (Emergency & Triage)
        $floor0Id = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $buildingId,
            'location_type' => 'floor',
            'code' => 'FL-00',
            'name' => 'Ground Floor',
            'status' => 'active'
        ]);

        $edWardId = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $floor0Id,
            'location_type' => 'ward',
            'code' => 'WRD-ED-01',
            'name' => 'Emergency & Acute Assessment Unit',
            'specialty' => 'emergency',
            'care_level' => 'observation',
            'status' => 'active'
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $roomId = self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $edWardId,
                'location_type' => 'room',
                'code' => "ED-BAY-0{$i}",
                'name' => "Triage Assessment Bay {$i}",
                'accommodation_type' => 'public',
                'status' => 'active'
            ]);
            self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $roomId,
                'location_type' => 'bed',
                'code' => "ED-BED-0{$i}",
                'name' => "Observation Bed {$i}",
                'care_level' => 'observation',
                'accommodation_type' => 'public',
                'status' => 'active'
            ]);
        }

        // 4. Floor 1 (Maternity & Pediatrics)
        $floor1Id = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $buildingId,
            'location_type' => 'floor',
            'code' => 'FL-01',
            'name' => 'First Floor',
            'status' => 'active'
        ]);

        $matWardId = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $floor1Id,
            'location_type' => 'ward',
            'code' => 'WRD-MAT-01',
            'name' => 'Maternity & Postnatal Ward',
            'specialty' => 'obstetrics',
            'care_level' => 'general',
            'status' => 'active'
        ]);

        for ($r = 1; $r <= 2; $r++) {
            $roomId = self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $matWardId,
                'location_type' => 'room',
                'code' => "MAT-RM-10{$r}",
                'name' => "Postnatal Room 10{$r}",
                'accommodation_type' => 'semi_private',
                'status' => 'active'
            ]);
            self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $roomId,
                'location_type' => 'bed',
                'code' => "MAT-BED-10{$r}-A",
                'name' => "Bed 10{$r}-A",
                'care_level' => 'general',
                'accommodation_type' => 'semi_private',
                'status' => 'active'
            ]);
            self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $roomId,
                'location_type' => 'bed',
                'code' => "MAT-BED-10{$r}-B",
                'name' => "Bed 10{$r}-B",
                'care_level' => 'general',
                'accommodation_type' => 'semi_private',
                'status' => 'active'
            ]);
        }

        // 5. Floor 2 (Adult Medical & Surgical)
        $floor2Id = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $buildingId,
            'location_type' => 'floor',
            'code' => 'FL-02',
            'name' => 'Second Floor',
            'status' => 'active'
        ]);

        $medWardId = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $floor2Id,
            'location_type' => 'ward',
            'code' => 'WRD-MED-01',
            'name' => 'General Medicine & Surgical Ward',
            'specialty' => 'general_medicine',
            'care_level' => 'general',
            'status' => 'active'
        ]);

        for ($r = 1; $r <= 3; $r++) {
            $roomId = self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $medWardId,
                'location_type' => 'room',
                'code' => "MED-RM-20{$r}",
                'name' => "Medical Room 20{$r}",
                'accommodation_type' => 'public',
                'status' => 'active'
            ]);
            self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $roomId,
                'location_type' => 'bed',
                'code' => "MED-BED-20{$r}-A",
                'name' => "Bed 20{$r}-A",
                'care_level' => 'general',
                'accommodation_type' => 'public',
                'status' => 'active'
            ]);
            self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $roomId,
                'location_type' => 'bed',
                'code' => "MED-BED-20{$r}-B",
                'name' => "Bed 20{$r}-B",
                'care_level' => 'general',
                'accommodation_type' => 'public',
                'status' => 'active'
            ]);
        }

        // 6. Floor 3 (Intensive Care & Critical Units)
        $floor3Id = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $buildingId,
            'location_type' => 'floor',
            'code' => 'FL-03',
            'name' => 'Third Floor',
            'status' => 'active'
        ]);

        $icuWardId = self::addLocation([
            'facility_id' => $facilityId,
            'parent_id' => $floor3Id,
            'location_type' => 'ward',
            'code' => 'WRD-ICU-01',
            'name' => 'Intensive Care Unit (ICU)',
            'specialty' => 'critical_care',
            'care_level' => 'icu',
            'status' => 'active'
        ]);

        for ($b = 1; $b <= 3; $b++) {
            $roomId = self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $icuWardId,
                'location_type' => 'room',
                'code' => "ICU-RM-30{$b}",
                'name' => "ICU Negative-Pressure Suite 30{$b}",
                'accommodation_type' => 'isolation',
                'status' => 'active'
            ]);
            self::addLocation([
                'facility_id' => $facilityId,
                'parent_id' => $roomId,
                'location_type' => 'bed',
                'code' => "ICU-BED-30{$b}",
                'name' => "ICU Ventilated Bed {$b}",
                'care_level' => 'icu',
                'accommodation_type' => 'isolation',
                'status' => 'active'
            ]);
        }
    }
}
