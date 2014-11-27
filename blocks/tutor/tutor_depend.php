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

	?>
<html>
 <script language="JavaScript">
   function Selecionar()
   {
     document.listaPreReq.action="tutor_prereq.php?id=<?php echo $id ?>";
     document.forms.listaPreReq.submit();
   }
  </script>
  <script language="JavaScript">
   function Voltar()
   {
     document.listaPreReq.action="tutor_lista.php?id=<?php echo $id ?>&acao=voltar";
     document.forms.listaPreReq.submit();
   }
 </script>

<p><b>Recurso e atividade iniciais</b></p>
<?php
foreach ($cms as $cm) {	
	if ($cm->id == $_SESSION['idRecInic']){
			$_SESSION['recInic'] = $cm->name;			
			echo "Recurso Inicial: ".$cm->name."<br>";		
		}
}
?>
<?php
foreach ($modinfo->instances['assign'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}		
	if ($cm->id == $_SESSION['idAtivInic']){
		$_SESSION['ativInic']= $cm->name;		
		echo "Atividade Inicial: ".$cm->name."<br>";				
		}
	}
	
	foreach ($modinfo->instances['forum'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}	

		if (($cm->name != 'News forum') && ($cm->name != 'Fórum de notícias')) {
			if ($cm->id == $_SESSION['idForumInic']){
				$_SESSION['forumInic'] = $cm->name;				
				echo "Atividade Inicial: ".$cm->name."<br>";					
			}
		}
	}
?>

<form name="editar" method="post" action="<?php echo "tutor_form.php?id=".$id."&amp;acao=editar"?>" value="editar">
	<input type="submit" style="margin:10px 10px 10px 0; width: 100px;" value="Editar"/>
</form>
<p><b>Atenção:</b> Editar os recursos e atividades iniciais implica em apagar no banco de dados todos os pré-requisitos definidos até o momento.</p>

 <?php  $recAtividade = isset($_POST['recAtiv']) ? $_POST['recAtiv'] : NULL;

	foreach ($cms as $cm) {
		if ($cm->id == $recAtividade){
			$_SESSION['selecionada_id'] = $cm->id;			
			$_SESSION['selecionada'] = $cm->name;
		}
	}
    foreach ($modinfo->instances['assign'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}		
	if ($cm->id == $recAtividade){	
		$_SESSION['selecionada_id'] = $cm->id;
		$_SESSION['selecionada'] = $cm->name;		
		}
	}	
	foreach ($modinfo->instances['forum'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}			
		if ($cm->id == $recAtividade){	
			$_SESSION['selecionada_id'] = $cm->id;
			$_SESSION['selecionada'] = $cm->name;
			}
		}
$result2 = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND rec_ativ_id = ? AND pre_req_id > ?', array( $id , $_SESSION['selecionada_id'],'0' ));
foreach ($result2 as $res2){	
			$arrPreRec[$res2->rec_ativ_id][] = $res2->pre_req_id;			
	}
foreach($arrPreRec as $rec){		
			foreach ($rec as $re){
				$array_rec[] = $re;
			}
}
$result5 = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND pre_req_id > ?', array( $id , '0' ));
foreach ($result5 as $res5){			
			if ($res5->pre_req_id == $_SESSION['selecionada_id']){
				$arrRecAtivDoPre[$_SESSION['selecionada_id']][] = $res5->rec_ativ_id;
			}
}

foreach($arrRecAtivDoPre as $RADP){		
			foreach ($RADP as $rp){
				$array_atPre[] = $rp;				
			}
}
	
?> 
 <br>
 <p>Selecione um recurso ou uma atividade para informar seus pré-requisitos</p>
	<p><b>Recursos e atividades:</b></p>
  <SELECT style="margin-right:15px; width:120px" NAME="selecionada">
			<OPTION SELECTED><?php echo $_SESSION['selecionada'] ?></OPTION></SELECT>
  <br><br><p><b>Selecione os pré-requisitos</b></p>
   <p>Recursos:</p>
 <form name="listaPreReq" method="post">  
<?php 

	foreach ($cms as $cm) {
			$recCont++;
			if (!isset($resources[$cm->modname][$cm->instance])) {
				continue;
			}
			//$resource = $resources[$cm->modname][$cm->instance];
			if ($cm->name != $_SESSION['selecionada'] && !in_array($cm->id, $array_atPre)){
				$impRec++;						
			if (in_array($cm->id, $array_rec)){?>		
					<input style="margin-right:5px; margin-left:70px;" type="checkbox" name="prereq[]" value="<?php echo $cm->id ?>" checked><?php echo $cm->name?>
					<br>
		<?php 	}else{?>		
					<input style="margin-right:5px; margin-left:70px;" type="checkbox" name="prereq[]" value="<?php echo $cm->id ?>" ><?php echo $cm->name?>
					<br>
		<?php 	}								
			}else if ($impRec == 0 && $recCont != 1){ 			
					echo "<br>";
					echo 'Não há recursos disponíveis na disciplina para ser pré-requisito';
				}			
		}
		
		echo "<br>";
		?>
	Atividades:<br>	
	<?php foreach ($modinfo->instances['assign'] as $cm) {
		$AtiCont++;
		if (!$cm->uservisible) {
			continue;
		}
		if ($cm->name != $_SESSION['selecionada'] && !in_array($cm->id, $array_atPre)){
			$impAtiv++;			
			if (in_array($cm->id, $array_rec)){?>		
							<input style="margin-right:5px; margin-left:70px;" type="checkbox" name="prereq[]" value="<?php echo $cm->id ?>" checked><?php echo $cm->name?>
							<br>
				<?php 	}else{?>		
							<input style="margin-right:5px; margin-left:70px;" type="checkbox" name="prereq[]" value="<?php echo $cm->id ?>" ><?php echo $cm->name?>
							<br>
				<?php }			
		} else if($impAtiv == 0 && $AtiCont != 1){ 
					echo "<br>";
					echo 'Não há atividades disponíveis na disciplina para ser pré-requisito';}
	}	
	echo "<br>";
	echo "Fóruns:<br>";
	foreach ($modinfo->instances['forum'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}	

		if (($cm->name != 'News forum') && ($cm->name != 'Fórum de notícias')) { 
		$ForCont++;		
			if ($cm->name != $_SESSION['selecionada'] && !in_array($cm->id, $array_atPre)){
				$impFor++;
				if (in_array($cm->id, $array_rec)){?>		
							<input style="margin-right:5px; margin-left:70px;" type="checkbox" name="prereq[]" value="<?php echo $cm->id ?>" checked><?php echo $cm->name?>
							<br>
				<?php 	}else{?>		
							<input style="margin-right:5px; margin-left:70px;" type="checkbox" name="prereq[]" value="<?php echo $cm->id ?>" ><?php echo $cm->name?>
							<br>
				<?php }
			} else if ($impFor == 0 && $ForCont != 1){ 
					echo "<br>";
					echo 'Não há fóruns disponíveis na disciplina para ser pré-requisito';}
		} else{
					echo "<br>";
					echo '<p style="margin-left:70px;"><b>Obs:</b> O fórum de notícias não pode ser usado como pré-requisito</p>';
		}
	}	?>
	
		<br><br><input type="button"  style="width: 100px;" value="Voltar" onClick="Voltar()"/>
		<input type="button" style="margin-left:65px; width: 100px;" value="Seleciona" onClick="Selecionar()"/>
	</form>

	

</html>
<?php


echo $OUTPUT->footer();


