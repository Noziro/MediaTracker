<?php
require_once $_SERVER["DOCUMENT_ROOT"].'/server/server.php';
require_once PATH.'server/upload.php';

if( API_ACTION === '/user/settings/update' ){
	// ALL SETTINGS

	// Variables which will be added onto as settings are changed.

	$to_update = [];
	$error_list = [];

	// $to_update contains information on what SQL to update.
	// It will follow the format: table => [column, type, value]
	// It may look something like this:
	//
	// $to_update = [
	//   'users' => [
	//     [
	//       'column' =>'nickname',
	//       'type' => 's',
	//       'value' => 'xXSnipesXx'
	// 	   ],
	// 	   [
	//       'column' =>'password',
	//       'type' => 's',
	//       'value' => '12345'
	// 	   ]
	//   ],	
	//   'user_preferences' => [
	// 	   [
	//       'column' =>'timezone',
	//       'type' => 's',
	//       'value' => 'UTC'
	//     ],
	//   ]	
	// ]

	// $error_list contains multiple sub-arrays containing error code/type/detail combinations to be fed to bailout()
	//
	// $error_list = [
	// 	 ['database_failure', 'error', 'About was not updated.'],
	// 	 ['invalid_name', 'error'],
	// 	 ['blank', 'generic', 'Just a notice']
	// ]


	$user_extra = sql('SELECT about FROM users WHERE id=?', ['i', $user['id']]);

	// NICKNAME
	if( isset($_POST['nickname']) ){
		$nick = trim($_POST['nickname']);

		// If not valid input
		if( !valid_name($nick) || $nick === '' ){
			$error_list[] = ['invalid_name', 'error'];
		}
		// If value valid and not the same as before 
		elseif( $nick !== $user['nickname'] ){
			$to_update['users'][] = [
				'column' => 'nickname',
				'type' => 's',
				'value' => $nick
			];
		}
	}

	// ABOUT
	if( isset($_POST['about']) ){
		$about = $_POST['about'];

		// If value not the same as before
		if( $about !== $user_extra->rows[0]['about'] ){
			// If valid, continue
			$to_update['users'][] = [
				'column' => 'about',
				'type' => 's',
				'value' => $about
			];
		}
	}

	// PROFILE IMAGE
	if( array_key_exists('profile_image', $_FILES) && $_FILES['profile_image']['name'] !== '' ){
		$uploaded = upload_image($_FILES['profile_image'], 'user_avatar');
		if( !$uploaded->ok ){
			$error_list[] = [$uploaded->notice->code, $uploaded->notice->message, $uploaded->notice->details];
		} else {
			$to_update['users'][] = [
				'column' => 'profile_image',
				'type' => 's',
				'value' => $uploaded->path
			];
		}
	}

	// BANNER IMAGE
	if( array_key_exists('banner_image', $_FILES) && $_FILES['banner_image']['name'] !== '' ){
		$uploaded = upload_image($_FILES['banner_image'], 'user_banner');
		if( !$uploaded->ok ){
			$error_list[] = [$uploaded->notice->code, $uploaded->notice->message, $uploaded->notice->details];
		} else {
			$to_update['users'][] = [
				'column' => 'banner_image',
				'type' => 's',
				'value' => $uploaded->path
			];
		}
	}

	// EMAIL
	//if( isset($_POST['email']) ){
	//	
	//}

	// PASSWORD
	// TODO - a lot of this password validation should be done in a single place instead of repeatedly, to avoid future problems.
	// Currently, the password validation can be found here, in session.php, and in the Authentication class of server.php 
	if( isset($_POST['previous_password']) ){
		if(
			!array_key_exists('new_password', $_POST)
			|| !array_key_exists('new_password_confirm', $_POST)
			|| strlen($_POST['previous_password']) === 0
			|| strlen($_POST['new_password']) === 0
			|| strlen($_POST['new_password_confirm']) === 0
		) {
			$error_list[] = ['blank', 'error', 'Please complete all three password fields.'];
		} else {
			$prev = $_POST['previous_password'];
			$new = $_POST['new_password'];
			$conf = $_POST['new_password_confirm'];

			$stmt = sql('SELECT password FROM users WHERE id=?', ['i', $user['id']]);
			if( !$stmt->ok || $stmt->row_count < 1 ){
				$error_list[] = [$stmt['error_code'], $stmt['error_type']]; 
			}
			elseif( !password_verify($prev, $stmt->rows[0]['password']) ){
				$error_list[] = ['blank', 'error', 'Current password was incorrect.'];
			}
			elseif( $new !== $conf ){
				$error_list[] = ['register_match', 'error'];
			}
			elseif( strlen($new) < 6 || strlen($new) > 72 ){
				$error_list[] = ['invalid_pass', 'error'];
			}
			else {
				// If valid, continue
				$new_hashed = password_hash($new, PASSWORD_BCRYPT);
				
				$to_update['users'][] = [
					'column' => 'password',
					'type' => 's',
					'value' => $new_hashed
				];
			}
		}
	}

	// TIMEZONE
	if( isset($_POST['timezone']) ){
		$tz = $_POST['timezone'];

		// If value not the same as before
		if( $tz !== $user['timezone'] ){
			// If not valid input
			$needle = false;
			foreach( VALID_TIMEZONES as $zone_group ){
				if( in_array($tz, $zone_group, True) ){
					$needle = true;
					break;
				}
			}

			if( $needle === false ){
				$error_list[] = ['invalid_value', 'error', 'Please choose a valid timezone'];
			}

			// If valid, continue
			$to_update['users'][] = [
				'column' => 'timezone',
				'type' => 's',
				'value' => $tz
			];
		}
	}

	// PROFILE COLOUR
	if( array_key_exists('reset_profile_colour', $_POST) && $_POST['reset_profile_colour'] == 1 ){
		$to_update['users'][] = [
			'column' => 'profile_colour',
			'type' => 's',
			'value' => null
		];
	}
	elseif( isset($_POST['profile_colour']) ){
		$col = $_POST['profile_colour'];

		// If value not the same as before and not default
		// TODO - hardcoding the default colour like this and preventing users from permanently setting it is unwanted behaviour. Improve this later.
		if( $col !== $user['profile_colour'] && $col !== '#ff3333' ){
			// If not valid input
			if(
				strlen($col) !== 7
				|| preg_match('/#([a-f0-9]{3}){1,2}\b/i', $col) < 1
			) {
				$error_list[] = ['blank', 'error', 'Please choose a valid profile colour.'];
			}

			// If valid, continue
			$to_update['users'][] = [
				'column' => 'profile_colour',
				'type' => 's',
				'value' => $col
			];
		}
	}

	// Start updating database
	if( count($to_update) > 0 ){
		// Setup basic variables
		$columns = [];
		$types = '';
		$values = [];
		
		foreach( $to_update as $table => $updates ){
			if( $table === 'users' ){
				$query = "UPDATE {$table} SET %columns% WHERE id=?";
			} else {
				$query = "UPDATE {$table} SET %columns% WHERE user_id=?";
			}

			$query = str_replace('%table%', $table, $query);

			foreach( $updates as $upd ){
				$columns[] = $upd['column'].'=?';
				$types .= $upd['type'];
				$values[] = $upd['value'];
			}
		}

		// Format columns
		$columns = implode(', ', $columns);
		$query = str_replace('%columns%', $columns, $query);

		// Add user ID onto end of params.
		$types .= 'i';
		$values[] = $user['id'];
		$params = array_merge([$types], $values);

		// Execute statement
		$stmt = sql($query, $params);
		if( !$stmt->ok ){
			$error_list[] = [$stmt->response_code, $stmt->response_type, $query.var_export($params, true)];
		} else {
			$changed = true;
		}
	} else {
		$changed = false;
	}

	// bailout
	if( $changed && count($error_list) > 0 ){
		bailout($return_to, ['partial_success'], ...$error_list);
	} elseif( $changed ){
		bailout($return_to, 'success');
	} else {
		bailout($return_to, ['no_change_detected'], ...$error_list);
	}
}
?>