<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php

/**
 * Script to display devices
 *
 */

# verify that user is logged in
$User->check_user_session();
# filter devices or fetch print all?
$devices = $Tools->fetch_all_objects("devices", "hostname");
$device_types = $Tools->fetch_all_objects ("deviceTypes", "tid");

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);

# Set dev_counter
$dev_counter = 0;

# Set devPage from get, or default to 1
if(isset($_GET['devPage'])) {
	$dev_page = $_GET['devPage'];
} else {
	$dev_page = 1;
}

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');
# get hidden fields */
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['devices']) ? $hidden_fields['devices'] : array();

# title
print "<h4>"._('List of network devices')."</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
	//back button
	if(isset($_GET['sPage'])) { print "<a class='btn btn-sm btn-default' href='javascript:history.back()' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> ". _('Back')."</a>"; }
	//administer
	elseif($User->is_admin(false)) { print "<a class='btn btn-sm btn-default' href='".create_link("administration","devices")."' data-action='add'  data-switchid='' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> ". _('Manage')."</a>"; }
print "</div>";

# table
print '<table id="switchManagement" class="table sorted table-striped table-top">';

#headers
print "<thead>";
print '<tr>';
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by hostname')."'>"._('Name')."</span></th>";
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by IP address')."'>"._('IP address')."</span></th>";
print "	<th><span rel='tooltip' data-container='body' title='"._('Sort by description')."'>"._('Description').'</span></th>';
print "	<th style='color:#428bca'>"._('Number of hosts').'</th>';
print "	<th class='hidden-sm'>          <span rel='tooltip' data-container='body' title='"._('Sort by type')."'>". _('Type').'</span></th>';

if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_fields)) {
			print "<th class='hidden-sm hidden-xs hidden-md'><span rel='tooltip' data-container='body' title='"._('Sort by')." $field[name]'>".$field['name']."</th>";
			$colspanCustom++;
		}
	}
}
print '	<th class="actions"></th>';
print '</tr>';
print "</thead>";

// no devices
if($devices===false) {
	$colspan = 8 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	foreach ($devices as $device) {
		//cast
		$dev_counter++;
		$device = (array) $device;
	
		if($dev_counter > ($dev_page-1)*50 && $dev_counter < $dev_page*50) {
	
			//count items
			$cnt1 = $Tools->count_database_objects("ipaddresses", "switch", $device['id']);
			$cnt2 = $Tools->count_database_objects("subnets", "device",  $device['id']);
			$cnt = $cnt1 + $cnt2;
		
			// reindex types
			if (isset($device_types)) {
				foreach($device_types as $dt) {
					$device_types_indexed[$dt->tid] = $dt;
				}
			}
		
			//print details
			print '<tr>'. "\n";
		
			print "	<td><a href='".create_link("tools","devices",$device['id'])."'><i class='fa fa-desktop'></i> ". $device['hostname'] .'</a></td>'. "\n";
			print "	<td>". $device['ip_addr'] .'</td>'. "\n";
			print '	<td class="description">'. $device['description'] .'</td>'. "\n";
			print '	<td><span class="badge badge1 badge5">'. $cnt .'</span> '._('Objects').'</td>'. "\n";
			print '	<td class="hidden-sm">'. $device_types_indexed[$device['type']]->tname .'</td>'. "\n";
		
			//custom
			if(sizeof(@$custom_fields) > 0) {
				foreach($custom_fields as $field) {
					if(!in_array($field['name'], $hidden_fields)) {
						// create html links
						$device[$field['name']] = $User->create_links($device[$field['name']], $field['type']);
		
						print "<td class='hidden-sm hidden-xs hidden-md'>".$device[$field['name']]."</td>";
					}
				}
			}
		
			print '	<td class="actions"><a href="'.create_link("tools","devices",$device['id']).'" class="btn btn-sm btn-default"><i class="fa fa-angle-right"></i> '._('Show details').'</a></td>';
			print '</tr>'. "\n";
		
		}
	}

		# print for unspecified
		print '<tr class="unspecified">'. "\n";
	
	    // count empty
		$cnt1 = $Tools->count_database_objects("ipaddresses", "switch", 0);
		$cnt2 = $Tools->count_database_objects("subnets", "device", 0);
		$cnt = $cnt1 + $cnt2;
	
	
		print '	<td>'._('Device not specified').'</td>'. "\n";
		print '	<td></td>'. "\n";
		print '	<td></td>'. "\n";
		print '	<td><span class="badge badge1 badge5">'. $cnt .'</span> '._('Objects').'</td>'. "\n";
		print '	<td class="hidden-sm"></td>'. "\n";
	
		//custom
		if(sizeof(@$custom_fields) > 0) {
			foreach($custom_fields as $field) {
				if(!in_array($field['name'], $hidden_fields)) {
					print "<td class='hidden-sm hidden-xs hidden-md'></td>";
				}
			}
		}
		print '	<td class="actions"></td>';
		print '</tr>'. "\n";
	}
		
print '</table>';

# Code to print page tabs without JS
if ($dev_counter > 50) {
	$page = 0;
	$total_pages = $dev_counter / 50;
	#print '<nav id="table_nav">'."\n";
	print '<ul class="pagination input-sm">'."\n";
	if ($dev_page == 1) {
		print '<li class="disabled"><a><<</a></li>'."\n";
	} else {
		print '<li><a href="?page=tools&section=devices&devPage=1"><<</a></li>'."\n";
	}

	while ($total_pages > $page) {
		$page++;
		if ($dev_page == $page) {
			print '<li class="active"><a href="?page=tools&section=devices&devPage='.$page.'">'.$page.'</a></li>'."\n";
		} else {
			print '<li><a href="?page=tools&section=devices&devPage='.$page.'">'.$page.'</a></li>'."\n";
		}
	}
			
	if ($dev_page == $page) {
		print '<li class="disabled"><a>>></a></li>'."\n";
	} else {
		print '<li><a href="?page=tools&section=devices&devPage='.$page.'">>></a></li>'."\n";
	}
	
	print '</ul>';
	print '</nav>';
}

?>
