<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * List of all resource type modules in course
 *
 * @package   moodlecore
 * @copyright 2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once("$CFG->libdir/resourcelib.php");

$id = required_param('id', PARAM_INT); // course id
$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

$PAGE->set_pagelayout('course');
require_course_login($course, true);

// get list of all resource-like modules
$allmodules = $DB->get_records('modules', array('visible'=>1));
$modules = array();
foreach ($allmodules as $key=>$module) {
    $modname = $module->name;
    $libfile = "$CFG->dirroot/mod/$modname/lib.php";
    if (!file_exists($libfile)) {
        continue;
    }
    $archetype = plugin_supports('mod', $modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
    if ($archetype != MOD_ARCHETYPE_RESOURCE) {
        continue;
    }

    $modules[$modname] = get_string('modulename', $modname);
	
    //some hacky nasic logging
    add_to_log($course->id, $modname, 'view all', "index.php?id=$course->id", '');
	
}

$strresources    = get_string('resources');
$stractivities   = get_string('activities');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

//$PAGE->set_url('/course/resources.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strresources);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strresources.' + '.$stractivities);

echo $OUTPUT->header();

$modinfo = get_fast_modinfo($course); // $modinfo->cms é um array

$cms = array();
$resources = array();
$assignment = array();


foreach ($modinfo->cms as $cm) {

    if (!$cm->uservisible) {
        continue;
    }
	if (!array_key_exists($cm->modname, $modules)) {
        continue;
    }

    if (!$cm->has_view()) {
        // Exclude label and similar
        continue;
    }
	
    $cms[$cm->id] = $cm;	
    $resources[$cm->modname][] = $cm->instance;

}

// preload instances

foreach ($resources as $modname=>$instances) {
	$resources[$modname] = $DB->get_records_list($modname, 'id', $instances, 'id', 'id,name,intro,introformat,timemodified');		
}

if (!$cms) {
    notice(get_string('thereareno', 'moodle', $strresources), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

	?>
<html>

<p><b>Recurso e atividade iniciais</b></p>
<?php

foreach ($cms as $cm) {	
	if ($cm->id == $_SESSION['idRecInic']){
			echo "Recurso Inicial: ".$cm->name."<br>";		
		}
}
?>
<?php
foreach ($modinfo->instances['assignment'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}		
	if ($cm->id == $_SESSION['idAtivInic']){		
		echo "Atividade Inicial: ".$cm->name."<br>";				
		}
	}
	
	foreach ($modinfo->instances['forum'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}	

		if (($cm->name != 'News forum') && ($cm->name != 'Fórum de notícias')) {
			if ($cm->id == $_SESSION['idForumInic']){
				echo "Atividade Inicial: ".$cm->name."<br>";					
			}
		}
	}

?>

<form name="editar" method="post" action="<?php echo "tutor_form.php?id=".$id."&amp;acao=editar"?>" value="editar">
	<input type="submit" style="margin:10px 10px 10px 0; width: 100px;" value="Editar"/>
</form>
<p><b>Atenção:</b> Editar os recursos e atividades iniciais implica em apagar no banco de dados todos os pré-requisitos definidos até o momento.</p>

 <br>
 <p>Selecione um recurso ou uma atividade para informar seus pré-requisitos</p>
	<p><b>Recursos/atividade:</b></p>
  <SELECT style="margin-right:15px; width:120px" NAME="selecionada">
			<OPTION SELECTED><?php echo $_SESSION['selecionada'] ?></OPTION></SELECT>
  <br><br><p><b>Pré-requisitos selecionados</b></p>
   <?php  

$params[] = $id;
$params[] = $_SESSION['selecionada_id'];	
$DB->delete_records_select('tutor_dependencia', 'curso_id = ? AND rec_ativ_id = ?', $params);   
  
$preRequi = isset($_POST['prereq']) ? $_POST['prereq'] : NULL;

$result2 = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND rec_ativ_id = ? AND pre_req_id > ?', array( $id , $_SESSION['selecionada_id'],'0'));
foreach ($result2 as $res2){	
			$arrPreReq[$res2->rec_ativ_id][] = $res2->pre_req_id;		
	}
foreach($arrPreReq as $rec){		
			foreach ($rec as $re){
				$array_rec[] = $re;
	}
}

foreach($preRequi as $pre){	
	foreach ($cms as $cm) {	
		if ($cm->id == $pre){
			$rec_cont++;
			if ($rec_cont == '1'){
				echo '<b>Recursos:</b><br>';}
				echo '<p style="margin-left:75px;">'.$cm->name.'</p>';
				if (!in_array($cm->id, $array_rec)){
					$record = new stdClass();
					$record->curso_id = $id;
					$record->rec_ativ_id = $_SESSION['selecionada_id'];
					$record->pre_req_id = $cm->id;
					$DB->insert_record('tutor_dependencia', $record, false);
				}
		}
	}
    foreach ($modinfo->instances['assignment'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}		
		if ($cm->id == $pre){
			$at_cont++;		
			if ($at_cont == '1'){
				echo '<b>Atividades:</b><br>';}
				echo '<p style="margin-left:75px;">'.$cm->name.'</p>';
				if (!in_array($cm->id, $array_rec)){
					$record = new stdClass();
					$record->curso_id = $id;
					$record->rec_ativ_id = $_SESSION['selecionada_id'];
					$record->pre_req_id = $cm->id;
					$DB->insert_record('tutor_dependencia', $record, false);
				}
		}
	}	
	foreach ($modinfo->instances['forum'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}			
		if ($cm->id == $pre){			
			$for_cont++;
			if ($for_cont == '1'){
				echo '<b>Fóruns:</b><br>';}
				echo '<p style="margin-left:75px;">'.$cm->name.'</p>';
				if (!in_array($cm->id, $array_rec)){
					$record = new stdClass();
					$record->curso_id = $id;
					$record->rec_ativ_id = $_SESSION['selecionada_id'];
					$record->pre_req_id = $cm->id;
					$DB->insert_record('tutor_dependencia', $record, false);
				}				
			}
		}
}
	?> 
 <br> 

 <form name="continuar" method="post" action="tutor_lista.php?id=<?php echo $id ?>&acao=continuar">  
	Indicar pré-requisitos de outros recursos e atividades
		<input type="submit" value="Continuar" style="width: 100px;" />
	</form>
 <form name="finalizar" method="post" action="tutor_arvore.php?id=<?php echo $id ?>">  
		<br><br><input type="submit" style="width: 100px;" value="Finalizar" />
	</form>
</html>
<?php


echo $OUTPUT->footer();


