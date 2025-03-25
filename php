<?php
require_once 'config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Get the action from query parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Initialize response array
$response = [
    'success' => false,
    'data' => null,
    'error' => null
];

try {
    switch ($action) {
        case 'recommendations':
            // In a real application, these would be generated based on actual usage patterns
            // and stored in the database
            $recommendations = [
                [
                    'title' => 'Optimize HVAC Usage',
                    'description' => 'Your HVAC system shows higher than average usage during off-peak hours. Consider adjusting your thermostat schedule.',
                    'category' => 'HVAC',
                    'potential_savings' => 15,
                    'difficulty' => 'Easy'
                ],
                [
                    'title' => 'Smart Lighting Installation',
                    'description' => 'Installing motion sensors in less frequently used areas could reduce lighting costs significantly.',
                    'category' => 'Lighting',
                    'potential_savings' => 20,
                    'difficulty' => 'Medium'
                ],
                [
                    'title' => 'Peak Hour Usage',
                    'description' => 'Shift your major appliance usage to off-peak hours (before 4PM or after 9PM) to save on energy costs.',
                    'category' => 'Schedule',
                    'potential_savings' => 25,
                    'difficulty' => 'Easy'
                ],
                [
                    'title' => 'Appliance Maintenance',
                    'description' => 'Your refrigerator's power usage has increased by 15%. Consider checking the door seals and cleaning the coils.',
                    'category' => 'Maintenance',
                    'potential_savings' => 10,
                    'difficulty' => 'Medium'
                ]
            ];
            
            $response['success'] = true;
            $response['data'] = $recommendations;
            break;

        case 'analyze':
            // Get the device ID from query parameters
            $deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : 0;
            
            if (!$deviceId) {
                throw new Exception('Device ID is required');
            }

            // In a real application, this would analyze the device's usage patterns
            // and return personalized recommendations
            $analysis = [
                'current_efficiency' => rand(60, 95),
                'potential_savings' => rand(5, 30),
                'recommendations' => [
                    'Adjust usage schedule to off-peak hours',
                    'Consider upgrading to a more energy-efficient model',
                    'Regular maintenance could improve efficiency'
                ]
            ];
            
            $response['success'] = true;
            $response['data'] = $analysis;
            break;

        case 'tips':
            // Get the category from query parameters
            $category = isset($_GET['category']) ? $_GET['category'] : 'general';
            
            // In a real application, these would be stored in the database
            $tips = [
                'general' => [
                    'Use natural light when possible',
                    'Unplug devices when not in use',
                    'Regular maintenance improves efficiency'
                ],
                'hvac' => [
                    'Clean or replace filters monthly',
                    'Use programmable thermostats',
                    'Seal air leaks around windows and doors'
                ],
                'lighting' => [
                    'Switch to LED bulbs',
                    'Install motion sensors',
                    'Use task lighting instead of overhead lights'
                ]
            ];
            
            $response['success'] = true;
            $response['data'] = isset($tips[$category]) ? $tips[$category] : $tips['general'];
            break;

        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Send the response
echo json_encode($response);
