<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 */

require_once("../class2.php");
if (!getperms("M")) 
{
	header("location:".e_BASE."index.php");
	 exit;
}

// include_lan(e_LANGUAGEDIR.e_LANGUAGE.'/admin/lan_'.e_PAGE);
e107::lan('core','wmessage',true);

$e_sub_cat = 'wmessage';

require_once("auth.php");

require_once(e_HANDLER.'userclass_class.php');
require_once(e_HANDLER."ren_help.php");

$frm = e107::getForm();
$mes = e107::getMessage();

vartrue($action) == '';
if (e_QUERY) 
{
	$tmp = explode('.', e_QUERY);
	$action = $tmp[0];
	$sub_action = varset($tmp[1], '');
	$id = varset($tmp[2], 0);
	unset($tmp);
}

if($_POST)
{
	$e107cache->clear("wmessage");
}

if (isset($_POST['wm_update'])) 
{
	$data = $tp->toDB($_POST['data']);
	$wm_title = $tp->toDB($_POST['wm_caption']);
	$wmId = intval($_POST['wm_id']);
	welcome_adminlog('02', $wmId, $wm_title);
	//$message = ($sql->db_Update("generic", "gen_chardata ='{$data}',gen_ip ='{$wm_title}', gen_intdata='".$_POST['wm_active']."' WHERE gen_id=".$wmId." ")) ? LAN_UPDATED : LAN_UPDATED_FAILED;
	if ($sql->update("generic", "gen_chardata ='{$data}',gen_ip ='{$wm_title}', gen_intdata='".$_POST['wm_active']."' WHERE gen_id=".$wmId." "))
	{
		$mes->addSuccess(LAN_UPDATED);
	}
	else 
	{
		$mes->addError(LAN_UPDATED_FAILED); 
	}
}

if (isset($_POST['wm_insert'])) 
{
	$wmtext = $tp->toDB($_POST['data']);
	$wmtitle = $tp->toDB($_POST['wm_caption']);
	welcome_adminlog('01', 0, $wmtitle);
	//$message = ($sql->db_Insert("generic", "0, 'wmessage', '".time()."', ".USERID.", '{$wmtitle}', '{$_POST['wm_active']}', '{$wmtext}' ")) ? LAN_CREATED :  LAN_CREATED_FAILED ;
	if ($sql->db_Insert("generic", "0, 'wmessage', '".time()."', ".USERID.", '{$wmtitle}', '{$_POST['wm_active']}', '{$wmtext}' "))
	{
		$mes->addSuccess(LAN_CREATED);
	}
	else
	{
		$mes->addError(LAN_CREATED_FAILED); 
	}
}

if (isset($_POST['updateoptions'])) 
{
	$changed = FALSE;
	foreach (array('wm_enclose','wmessage_sc') as $opt)
	{
		$temp = intval($_POST[$opt]);
		if ($temp != $pref[$opt])
		{
			$pref[$opt] = $temp;
			$changed = TRUE;
		}
	}
	if ($changed)
	{
		save_prefs();
		welcome_adminlog('04', 0, $pref['wm_enclose'].', '.$pref['wmessage_sc']);
	}
	else 
	{
		$mes->addInfo(LAN_NOCHANGE_NOTSAVED);
	}
}

if (isset($_POST['main_delete'])) 
{
	$del_id = array_keys($_POST['main_delete']);
	welcome_adminlog('03', $wmId, '');
	if ($sql->delete("generic", "gen_id='".$del_id[0]."' "))
	{
		$mes->addSuccess(LAN_DELETED);
	}
	else 
	{
		$mes->addError(LAN_DELETED_FAILED); 
	}
}
$ns->tablerender($caption, $mes->render() . $text);

// Show Existing -------
if ($action == "main" || $action == "") 
{
	if ($wm_total = $sql->select("generic", "*", "gen_type='wmessage' ORDER BY gen_id ASC")) 
	{
		$wmList = $sql->db_getList();
		$text = $frm->open('myform_wmessage','post',e_SELF);
		$text .= "
            <table class='table adminlist'>
			<colgroup>
				<col style='width:5%' />
				<col style='width:70%' />
				<col style='width:10%' />
				<col style='width:10%' />
   			</colgroup>
			<thead>
			<tr>
				<th>".LAN_ID."</th>
				<th>".WMLAN_02."</th>
				<th class='center'>".LAN_VISIBILITY."</th>
				<th class='center'>".LAN_OPTIONS."</th>
			</tr>
			</thead>
			<tbody>";

		foreach($wmList as $row) 
		{
			$text .= "
			<tr>
				<td class='center' style='text-align: center; vertical-align: middle'>".$row['gen_id']."</td>
				<td>".strip_tags($tp->toHTML($row['gen_ip']))."</td>
				<td>".r_userclass_name($row['gen_intdata'])."</td>
            	<td class='center nowrap'>
            		<a class='btn btn-large' href='".e_SELF."?create.edit.{$row['gen_id']}'>".ADMIN_EDIT_ICON."</a>
            		<input class='btn btn-large' type='image' title='".LAN_DELETE."' name='main_delete[".$row['gen_id']."]' src='".ADMIN_DELETE_ICON_PATH."' onclick=\"return jsconfirm('".LAN_CONFIRMDEL." [ID: {$row['gen_id']} ]')\"/>
				</td>
			</tr>";
		}

		$text .= "</tbody></table>";
		$text .= $frm->close();
	
	} else {
		$mes->addInfo(WMLAN_09);
	}
	
	$ns->tablerender(WMLAN_00.SEP.LAN_MANAGE, $mes->render() . $text);
}

// Create and Edit
if ($action == "create" || $action == "edit")
{

	if ($sub_action == "edit")
	{
		$sql->select("generic", "gen_intdata, gen_ip, gen_chardata", "gen_id = $id");
		$row = $sql->fetch();
	}

	$text = "
		<form method='post' action='".e_SELF."'  id='wmform'>
		<fieldset id='code-wmessage-create'>
        <table class='table adminform'>
		<colgroup>
			<col class='col-label' />
			<col class='col-control' />
		</colgroup>
		<tr>
			<td>".WMLAN_10."</td>
			<td>".$frm->text(wm_caption, $tp->toForm(vartrue($row['gen_ip'])), 80)."</td>
		</tr>
		<tr>
			<td>".WMLAN_04."</td>
			<td><textarea class='e-wysiwyg tbox' id='data' name='data' cols='70' rows='15' style='width:95%' onselect='storeCaret(this);' onclick='storeCaret(this);' onkeyup='storeCaret(this)'>".$tp->toForm(vartrue($row['gen_chardata']))."</textarea></td>
		</tr>";

	//	$text .= display_help("helpb", "admin"); //XXX Serves as BC Check 

	$text .= "
		<tr>
			<td>".LAN_VISIBILITY."</td>
			<td>".r_userclass("wm_active", vartrue($row['gen_intdata']), "off", "public,guest,nobody,member,admin,classes")."</td>
		</tr>
		</table>

		<div class='buttons-bar center'>";

			if($sub_action == "edit")
			{
		    	$text .= $frm->admin_button('wm_update', LAN_UPDATE, 'update');
			}
			else
			{
		    	$text .= $frm->admin_button('wm_insert', LAN_CREATE, 'create');
			}

	$text .= "<input type='hidden' name='wm_id' value='".$id."' />";
	$text .= "</div>
		</fieldset>
		</form>";
	
	$ns->tablerender(WMLAN_00.SEP.LAN_CREATE, $mes->render() . $text);
}


if ($action == "opt") {
	$pref = e107::getPref();
	$ns = e107::getRender();
	
	$text = "
		<form method='post' action='".e_SELF."?".e_QUERY."'>\n
		<fieldset id='code-wmessage-options'>
        <table class='table adminform'>
		<colgroup>
			<col class='col-label' />
			<col class='col-control' />
		</colgroup>
		<tr>
			<td>".WMLAN_05."</td>
			<td>".$frm->radio_switch('wm_enclose', varset($pref['wm_enclose']))."<span class='field-help'>".WMLAN_06."</span></td>
		</tr>";
	
	/*	DEPRECATED - see header_default.php {WMESSAGE}
	$text .= "
		<tr>
			<td>".WMLAN_07."</td>
			<td>".$frm->checkbox('wmessage_sc', 1, varset($pref['wmessage_sc'],0))."</td>
		</tr>";
	*/	
	
	$text .= "
		</table>

		<div class='buttons-bar center'>
			". $frm->admin_button('updateoptions', LAN_SAVE)."
		</div>
		</fieldset>
		</form>
		";

	$ns->tablerender(WMLAN_00.SEP.LAN_PREFS, $mes->render() . $text);
}

function wmessage_adminmenu() 
{

	$act = e_QUERY;
	$action = vartrue($act,'main');
	
	$var['main']['text'] = LAN_MANAGE;
	$var['main']['link'] = e_SELF;
	$var['create']['text'] = LAN_CREATE;
	$var['create']['link'] = e_SELF."?create";
	$var['opt']['text'] = LAN_PREFS;
	$var['opt']['link'] = e_SELF."?opt";

	show_admin_menu(WMLAN_00, $action, $var);
}

require_once("footer.php");



// Log event to admin log
function welcome_adminlog($msg_num='00', $id=0, $woffle='')
{
  global $pref, $admin_log;
//  if (!varset($pref['admin_log_log']['admin_welcome'],0)) return;
	$msg = '';
	if ($id) $msg = 'ID: '.$id;
	if ($woffle)
	{
		if ($msg) $msg .= '[!br!]';
		$msg .= $woffle;
	}
	$admin_log->log_event('WELCOME_'.$msg_num,$msg,E_LOG_INFORMATIVE,'');
}
?>