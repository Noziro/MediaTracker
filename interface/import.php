<?php
if( API_ACTION === '/import' ){
	bailout($return_to, 'unimplemented');
}
elseif( API_ACTION === '/export' ){
	bailout($return_to, 'unimplemented');
}
bailout($return_to, 'disallowed_action');
?>