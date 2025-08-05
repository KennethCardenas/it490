<?php

declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendMessage(array $payload): array {
    if (!isset($payload['type'])) {
        throw new InvalidArgumentException('Payload must contain a type');
    }

    switch ($payload['type']) {
        case 'login':
            if (empty($payload['username']) || empty($payload['password'])) {
                throw new InvalidArgumentException('Username and password are required');
            }
            break;

        case 'register':
            foreach (['username','email','password'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            break;

        case 'update_profile':
            foreach (['user_id','username','email'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            break;

        case 'create_dog':
            foreach (['user_id','name'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'get_dogs':
            if (empty($payload['user_id'])) {
                throw new InvalidArgumentException('user_id is required');
            }
            break;

        case 'add_task':
            foreach (['dog_id','user_id','title','due_date'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'get_tasks':
            if (empty($payload['dog_id'])) {
                throw new InvalidArgumentException('dog_id is required');
            }
            break;

        case 'toggle_task':
            if (empty($payload['task_id'])) {
                throw new InvalidArgumentException('task_id is required');
            }
            break;

        case 'logout':
            if (empty($payload['user_id'])) {
                throw new InvalidArgumentException('user_id is required for logout');
            }
            break;

        case 'get_water':
            if (empty($payload['dog_id'])) {
                throw new InvalidArgumentException('dog_id is required');
            }
            break;

        case 'add_water':
            foreach (['dog_id','user_id','amount'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

	case 'delete_dog':
            foreach (['dog_id', 'user_id'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

	case 'add_meal':
            foreach (['dog_id','user_id','meal_type','amount'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

    	case 'get_meals':
            if (empty($payload['dog_id'])) {
                throw new InvalidArgumentException('dog_id is required');
            }
            break;

    	case 'delete_meal':
            if (empty($payload['meal_id'])) {
                throw new InvalidArgumentException('meal_id is required');
            }
            break;

    	case 'delete_water':
            if (empty($payload['water_id'])) {
                throw new InvalidArgumentException('water_id is required');
            }
            break;

    	case 'delete_task':
            if (empty($payload['task_id'])) {
                throw new InvalidArgumentException('task_id is required');
            }
            break;

	case 'schedule_medication':
            foreach (['dog_id','user_id','medication','schedule_time'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'complete_medication':
            if (empty($payload['med_id'])) {
                throw new InvalidArgumentException('med_id is required');
            }
            break;

    	case 'delete_medication':
            foreach (['med_id', 'user_id'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

	case 'add_care_log':
            foreach (['dog_id','user_id','note'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

    	case 'get_care_logs':
            if (empty($payload['dog_id'])) {
                throw new InvalidArgumentException('dog_id is required');
            }
            break;

    	case 'delete_care_log':
            foreach (['log_id', 'user_id'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

	case 'add_behavior':
            foreach (['dog_id','user_id','behavior'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'get_behaviors':
            if (empty($payload['dog_id'])) {
                throw new InvalidArgumentException('dog_id is required');
            }
            break;

    	case 'delete_behavior':
            foreach (['behavior_id', 'user_id'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

	case 'get_points':
            if (empty($payload['user_id'])) {
                throw new InvalidArgumentException('user_id is required');
            }
            break;

	case 'get_achievements':
            if (empty($payload['user_id'])) {
                throw new InvalidArgumentException('user_id is required');
            }
            break;

        case 'invite_create':
            foreach (['user_id','dog_id','sitter_email','permission_level'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'invite_list':
            if (empty($payload['user_id'])) {
                throw new InvalidArgumentException('user_id is required');
            }
            break;

        case 'invite_update':
            foreach (['id','status'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'playdates_create':
            foreach ([
                'user_id', 'title', 'scheduled_at', 'location', 'age_range',
                'size', 'energy_level', 'temperament', 'play_style', 'gender_pref', 'description'
            ] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'playdates_list':
            // no validation needed
            break;

        case 'playdate_request':
            foreach (['user_id','playdate_id','recipient_id','location_preference'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'playdate_requests_list':
            if (empty($payload['user_id'])) {
                throw new InvalidArgumentException('user_id is required');
            }
            break;

        case 'playdate_requests_update':
            foreach (['id','status'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'lost_dogs_create':
            foreach (['user_id','dog_id','dog_name','last_lat','last_lng','alert_radius'] as $f) {
                if (!isset($payload[$f]) || $payload[$f] === '') {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        case 'lost_dogs_list':
            // no validation needed
            break;

        case 'lost_dogs_update':
            foreach (['id','status'] as $f) {
                if (empty($payload[$f])) {
                    throw new InvalidArgumentException("$f is required");
                }
            }
            break;

        default:
            throw new InvalidArgumentException("Unsupported message type: {$payload['type']}");
    }

    try {
        $connection = new AMQPStreamConnection('100.87.203.113', 5672, 'kac63', 'Linklinkm1!');
        $channel = $connection->channel();
        $channel->queue_declare('user_request_queue', false, false, false, false);

        list($callbackQueue,) = $channel->queue_declare('', false, false, true, true);
        $corrId = uniqid();

        $msg = new AMQPMessage(json_encode($payload), [
            'correlation_id' => $corrId,
            'reply_to' => $callbackQueue
        ]);

        $channel->basic_publish($msg, '', 'user_request_queue');

        $response = null;

        $channel->basic_consume($callbackQueue, '', false, true, true, false,
            function ($rep) use (&$response, $corrId) {
                if ($rep->get('correlation_id') === $corrId) {
                    $response = json_decode($rep->body, true);
                }
            });

        while (!$response) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return $response;
    } catch (Exception $e) {
        error_log('MQ Connection failed: ' . $e->getMessage());
        return ['status'=>'error','message'=>'Failed to send message: '.$e->getMessage()];
    }
}
