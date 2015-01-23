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

session_start();

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
$assign = array();


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

	/*$cm->modname; //nome do recurso (page, resource, forum)
	$cm->name; //nome dado ao recurso (leitura1, etc)
	$cm->instance; //nro instancia do recurso
	$cm->id; //id do recurso
	*/
}

// preload instances

foreach ($resources as $modname=>$instances) {
	$resources[$modname] = $DB->get_records_list($modname, 'id', $instances, 'id', 'id,name,intro,introformat,timemodified');		
}

if (!$cms) {
    notice(get_string('thereareno', 'moodle', $strresources), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

if (isset($_GET['acao'])){
	$acao = $_GET['acao'];
}
	?>
<html>

<p><b>Recurso e atividade iniciais</b></p>
<?php

$res = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND pre_req_id = ?', array( $id , '0' ));

foreach ($res as $re){
	$contaInic++;
}

if ($contaInic > 0 and $acao != 'voltar' and $acao != 'continuar'){
	$params1[] = $id;
	$DB->delete_records_select('tutor_dependencia', 'curso_id = ?', $params1);
}
$recInicial = isset($_POST['recurso']) ? $_POST['recurso'] : NULL;
    foreach ($cms as $cm) {
		if ($cm->id == $recInicial){
			$recu = true;
			echo "Recurso Inicial: ".$cm->name."<br>";					
			$_SESSION['idRecInic'] = $cm->id;				
				$record = new stdClass();
				$record->curso_id = $id;
				$record->rec_ativ_id = $_SESSION['idRecInic'];
				$record->pre_req_id = '0';									
				$DB->insert_record('tutor_dependencia', $record, false);			
		}
	}

$ativInicial = isset($_POST['atividade']) ? $_POST['atividade'] : NULL;
    foreach ($modinfo->instances['assign'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}		
	if ($cm->id == $ativInicial){
		$ati = true;
		echo "Atividade Inicial: ".$cm->name."<br>";
		$_SESSION['idForumInic'] = '';
		$_SESSION['idAtivInic'] = $cm->id;		
			$record = new stdClass();
			$record->curso_id = $id;
			$record->rec_ativ_id = $_SESSION['idAtivInic'];
			$record->pre_req_id = '0';
			$DB->insert_record('tutor_dependencia', $record, false);			
		}
	}
	
	foreach ($modinfo->instances['quiz'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}		
	if ($cm->id == $ativInicial){
		$ati = true;
		echo "Atividade Inicial: ".$cm->name."<br>";
		$_SESSION['idForumInic'] = '';
		$_SESSION['idAtivInic'] = $cm->id;		
			$record = new stdClass();
			$record->curso_id = $id;
			$record->rec_ativ_id = $_SESSION['idAtivInic'];
			$record->pre_req_id = '0';
			$DB->insert_record('tutor_dependencia', $record, false);			
		}
	}
	
	foreach ($modinfo->instances['forum'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}	

		if (($cm->name != 'News forum') && ($cm->name != 'Fórum de notícias')) {
			if ($cm->id == $ativInicial){
			$ati = true;
			$_SESSION['idAtivInic'] = '';
			echo "Atividade Inicial: ".$cm->name."<br>";
			$_SESSION['idForumInic'] = $cm->id;			
				$record = new stdClass();
				$record->curso_id = $id; 
				$record->rec_ativ_id = $_SESSION['idForumInic'];
				$record->pre_req_id = '0';
				$DB->insert_record('tutor_dependencia', $record, false);
			}
		}
	}

$result = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND pre_req_id = ?', array( $id , '0' ));
foreach ($result as $res){
	foreach ($cms as $cm){
		if (($cm->id == $res->rec_ativ_id) and ($recLista < '1') and (!$recu)){
			$recLista++;
			echo "Recurso Inicial: ".$cm->name.'<br>';
		}
	}
	foreach ($modinfo->instances['assign'] as $cm) {
		if (($cm->id == $res->rec_ativ_id) and ($ativLista < '1') and (!$ati)){
			$ativLista++;
			echo "Atividade Inicial: ".$cm->name.'<br>';
		}
	}
	foreach ($modinfo->instances['forum'] as $cm) {
		if (($cm->id == $res->rec_ativ_id) and ($forLista < '1') and (!$ati)){
			$forLista++;
			echo "Atividade Inicial: ".$cm->name.'<br>';
		}
	}
}
?>

<form name="editar" method="post" action="<?php echo "tutor_form.php?id=".$id."&amp;acao=editar"?>" value="editar">
	<input type="submit" style="margin:10px 10px 10px 0; width: 100px;" value="Editar"/>
</form>
<p><b>Atenção:</b> Editar os recursos e atividades iniciais implica em apagar no banco de dados todos os pré-requisitos definidos até o momento.</p>
 <?php
$result1 = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND pre_req_id > ?', array( $id , '0' ));
foreach ($result1 as $res1){		
	foreach ($cms as $cm) {				
		if ($cm->id == $res1->rec_ativ_id){ 		
			$arrRec[$res1->rec_ativ_id]++; 			
		}		
	}
	foreach ($modinfo->instances['assign'] as $cm) {
		if ($cm->id == $res1->rec_ativ_id){ 
			$arrAtiv[$res1->rec_ativ_id]++; 			
		}
	}

	foreach ($modinfo->instances['quiz'] as $cm) {
		if ($cm->id == $res1->rec_ativ_id){ 
			$arrQuiz[$res1->rec_ativ_id]++; 			
		}
	}
	
	foreach ($modinfo->instances['forum'] as $cm) {
		if ($cm->id == $res1->rec_ativ_id){ 
			$arrFor[$res1->rec_ativ_id]++; 			
		}
	}
}
?>
<br>
<form name="listaRecAtiv" method="post" action="tutor_depend.php?id=<?php echo $id ?>">
	<p>Selecione um recurso ou uma atividade para informar seus pré-requisitos</p>
	<p><b>Recursos e atividades:</b></p>
   <SELECT style="margin-right:15px; width:120px" NAME="recAtiv" required="required">
			<OPTION SELECTED></OPTION>
			<optgroup label="Recursos">
	<?php	
	foreach ($cms as $cm) {
	    if (!isset($resources[$cm->modname][$cm->instance])) {
			continue;
		}
	//	$resource = $resources[$cm->modname][$cm->instance];
		
		if ($cm->id != $_SESSION['idRecInic']){
			if ($arrRec[$cm->id] > 0){?>
				<OPTION value="<?php echo $cm->id ?>"><?php echo '(*) '.$cm->name?></OPTION>
				<?php } else { ?>
    		  <OPTION value="<?php echo $cm->id ?>"><?php echo $cm->name ?></OPTION>	
	<?php 	}
		}
	} ?>
	</optgroup>
	<optgroup label="Atividades">
	<?php foreach ($modinfo->instances['assign'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}
		if ($cm->id != $_SESSION['idAtivInic']){ 	
			if ($arrAtiv[$cm->id] > 0){?>
				<OPTION value="<?php echo $cm->id ?>"><?php echo '(*) '.$cm->name?></OPTION>
				<?php } else { ?>
    		  <OPTION value="<?php echo $cm->id ?>"><?php echo $cm->name ?></OPTION>	
	<?php 	}
		}
	}?>
	</optgroup>
	<optgroup label="Questionários">
<?php foreach ($modinfo->instances['quiz'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}
		if ($cm->id != $_SESSION['idAtivInic']){ 	
			if ($arrQuiz[$cm->id] > 0){?>
				<OPTION value="<?php echo $cm->id ?>"><?php echo '(*) '.$cm->name?></OPTION>
				<?php } else { ?>
    		  <OPTION value="<?php echo $cm->id ?>"><?php echo $cm->name ?></OPTION>	
	<?php 	}
		}
	}?>
	</optgroup>
	<optgroup label="Fóruns">
	<?php
	foreach ($modinfo->instances['forum'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}	

		if (($cm->name != 'News forum') && ($cm->name != 'Fórum de notícias')) { 
			if ($cm->id != $_SESSION['idForumInic']) {
				if ($arrFor[$cm->id] > 0){?>
					<OPTION value="<?php echo $cm->id ?>"><?php echo '(*) '.$cm->name?></OPTION>
				<?php } else { ?>
					<OPTION value="<?php echo $cm->id ?>"><?php echo $cm->name ?></OPTION>	
	<?php 			}
			}
		}
	}
	?>
	</optgroup>
	</SELECT>
		<br><br><input type="submit" style="width: 100px;" value="Seleciona" />
	</form>
</html>
<?php


echo $OUTPUT->footer();


