<?php
/**
 * Script to insert a specified number of unique catechumens and enroll them in a specific group.
 * Usage: docker exec docker-webserver-1 php /var/www/tools/populate_catechumens.php <count> <catechetical_year> <catechism> <group> [<created_by_username>]
 * Example: php tools/populate_catechumens.php 50 20252026 1 A admin
 */

use catechesis\PdoDatabaseManager;
use catechesis\DatabaseAccessMode;

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths
$rootDir = dirname(__DIR__) . '/html';
$coreDir = $rootDir . '/core';

// Include necessary core files
require_once $coreDir . '/config/catechesis_config.inc.php';
require_once $coreDir . '/PdoDatabaseManager.php';
require_once $coreDir . '/Utils.php';

// Check CLI arguments
if ($argc < 5) {
    echo "Usage: php " . $argv[0] . " <count> <catechetical_year> <catechism> <group> [<created_by_username>]\n";
    echo "Example: php " . $argv[0] . " 50 20252026 1 A admin\n";
    exit(1);
}

$count = (int)$argv[1];
$catecheticalYear = (int)$argv[2];
$catechism = (int)$argv[3];
$group = $argv[4];
$createdBy = $argv[5] ?? 'admin';

if ($count <= 0) {
    echo "Error: count must be a positive integer.\n";
    exit(1);
}

// Initialize Database Manager
$db = new PdoDatabaseManager();

try {
    // Try to connect as editor
    if (!$db->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT)) {
        echo "Error: Could not connect to the database.\n";
        exit(1);
    }

    echo "Starting insertion of $count catechumens into group Year $catechism, Class $group ($catecheticalYear)...\n";

    // Create a dummy family member to be the responsible person for these test catechumens
    $responsibleName = "Responsible for Test Group $catechism-$group";
    $responsibleFid = $db->createFamilyMember(
        $responsibleName,
        "Test Job",
        "Test Address",
        "1234-567",
        "987654321",
        "987654321",
        "test@example.com",
        true
    );

    if (!$responsibleFid) {
        echo "Error: Could not create dummy responsible person.\n";
        exit(1);
    }

    echo "Created dummy responsible person with FID: $responsibleFid\n";

    $successCount = 0;
    for ($i = 1; $i <= $count; $i++) {
        $uniqueSuffix = bin2hex(random_bytes(4));
        $name = "Catechumen $i - $uniqueSuffix";
        $birthDate = date('d-m-Y', strtotime("-" . (6 + $catechism) . " years -" . rand(0, 364) . " days"));
        $birthPlace = "Test City";
        $nif = null; // Can be null as per DB schema
        
        try {
            // Create Catechumen
            $cid = $db->createCatechumen(
                $name,
                $birthDate,
                $birthPlace,
                $nif,
                null, // father_fid
                null, // mother_fid
                $responsibleFid,
                "Encarregado de Educação", // responsible_relationship
                "", // photo
                0, // numSiblings
                false, // isScout
                true, // photosAllowed
                false, // allowedToGoOutAlone
                "Generated for testing purposes", // observations
                $createdBy
            );

            if ($cid) {
                // Enroll in group
                // enrollCatechumenInGroup(int $cid, int $catecheticalYear, int $catechism, string $group, bool $pass, bool $paid, string $username)
                $enrolled = $db->enrollCatechumenInGroup(
                    $cid,
                    $catecheticalYear,
                    $catechism,
                    $group,
                    false, // pass (enrollment doesn't mean they passed yet)
                    true,  // paid
                    $createdBy
                );

                if ($enrolled) {
                    $successCount++;
                    if ($successCount % 10 == 0 || $successCount == $count) {
                        echo "Processed $successCount/$count catechumens...\r";
                    }
                } else {
                    echo "\nWarning: Created catechumen $cid but failed to enroll in group.\n";
                }
            }
        } catch (Exception $e) {
            echo "\nError processing catechumen $i: " . $e->getMessage() . "\n";
        }
    }

    echo "\nSuccessfully created and enrolled $successCount catechumens.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
