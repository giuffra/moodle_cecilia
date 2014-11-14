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

 <br>
 <p>Arvore de Pré-requisitos da turma</p>
<?php  
   
$result = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ?', array($id));
foreach ($result as $res){	
			$arrPreReq[] = $res->pre_req_id.",".$res->rec_ativ_id;		
	}
foreach($arrPreReq as $rec){				
				$var = explode(",",$rec);
$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
if ($var[0]== '0'){
	$var[0] = "";//$course->shortname;
}
foreach ($cms as $cm) {	
	if ($cm->id == $var[0]){
			$string = str_replace(" ","_",$cm->name);
			$var[0] = $string;		
		}else if($cm->id == $var[1]){			
			$string = str_replace(" ","_",$cm->name);
			$var[1] = $string;		
		}
}
foreach ($modinfo->instances['assignment'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}		
	if ($cm->id == $var[0]){			
			$string = str_replace(" ","_",$cm->name);
			$var[0] = $string;
		}else if($cm->id == $var[1]){			
			$string = str_replace(" ","_",$cm->name);
			$var[1] = $string;
		}
	}
	
	foreach ($modinfo->instances['forum'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}	

		if (($cm->name != 'News forum') && ($cm->name != 'Fórum de notícias')) {
			if ($cm->id == $var[0]){			
			$string = str_replace(" ","_",$cm->name);
			$var[0] = $string;		
		}else if($cm->id == $var[1]){			
			$string = str_replace(" ","_",$cm->name);
			$var[1] = $string;
		}
		}
	}

	
				$graph = $graph.$var[0]."->".$var[1].";";	
}

//print_r($arrPreReq);

?>
<html>
	<img src="https://chart.googleapis.com/chart?cht=gv&chl=digraph{<?php echo $graph ?>}">
</html>
<?php

echo $OUTPUT->footer();


