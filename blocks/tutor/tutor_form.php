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

if (isset($_GET['acao'])){
	$acao = $_GET['acao'];
}

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


if (isset($_POST['perfilRec'])){
	$arrayteste = $_POST['perfilRec'];		
	$params[] = $id;
	$DB->delete_records_select('tutor_rec_at_perfil', 'curso_id = ?', $params);
	foreach ($arrayteste as $aP){
		$var = explode(",",$aP);	
		$record = new stdClass();
		$record->curso_id = $id;
		$record->rec_ativ_id = $var[0];
		$record->perfil_id = $var[1];		
		$DB->insert_record('tutor_rec_at_perfil', $record, false);
	}	
}

$result2 = $DB->get_records_sql('SELECT * FROM {tutor_rec_at_perfil} WHERE curso_id = ?', array( $id));
foreach ($result2 as $res2){
	$ativComPerfil[] = $res2->rec_ativ_id;
	$perfilDaAtivID[$res2->rec_ativ_id] = $res2->perfil_id;
	$contaAtividadesComPerfil++;
}

foreach ($cms as $cm) {
	if (!isset($resources[$cm->modname][$cm->instance])) {
		continue;
	}
	$resource = $resources[$cm->modname][$cm->instance];
	if ($cm->id == $perfilDaAtivID[$res2->rec_ativ_id]){
		$perfilDaAtivNome[$res2->rec_ativ_id] = $cm->name;
		}
	$contaRecAtNoCurso++;
} 
			
foreach ($modinfo->instances['assignment'] as $cm) {
	if (!$cm->uservisible) {
		continue;
	}
	if ($cm->id == $perfilDaAtivID[$res2->rec_ativ_id]){
		$perfilDaAtivNome[$res2->rec_ativ_id] = $cm->name;
		}
	$contaRecAtNoCurso++;
}

if ($acao == 'editar'){
	$params[] = $id;
	$DB->delete_records_select('tutor_dependencia', 'curso_id = ?', $params);
}

$result3 = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND pre_req_id = ?', array( $id ,'0' ));
foreach ($result3 as $res3){
	$contIniciais++;
}	

$result4 = $DB->get_records_sql('SELECT * FROM {tutor_perfil}');

if ($contaAtividadesComPerfil < $contaRecAtNoCurso){
?>	
	<form name="recAtForPerfil" method="post" action="tutor_form.php?id=<?php echo $id ?>">
	  <b></b>Selecione o nível das atividades (Geral, Básico, Médio, Avançado):</b><br><br>
	   <b>Recursos:</b><br><br>
<?php foreach ($cms as $cm) {
		if (!isset($resources[$cm->modname][$cm->instance])) {
				continue;
		}
		$resource = $resources[$cm->modname][$cm->instance];			
		?>
		<SELECT style="margin-right:10px; width:120px" NAME="perfilRec[]" required="required">
		<?php
		if (in_array($cm->id, $ativComPerfil)){
			foreach($result4 as $res4){	
				if ($res4->id == $perfilDaAtivID[$cm->id]) {?>		
				<OPTION SELECTED value="<?php echo $cm->id.",".$res4->id; ?>"><?php echo $res4->nome ?></OPTION> <?php }else{ ?>
				<OPTION value="<?php echo $cm->id.",".$res4->id; ?>"><?php echo $res4->nome ?></OPTION><?php				
			}}
		}else{	
			?>
			<OPTION SELECTED></OPTION><?php
			foreach($result4 as $res4){	?>		
				<OPTION value="<?php echo $cm->id.",".$res4->id; ?>"><?php echo $res4->nome ?></OPTION><?php				
			}}?> 
		</SELECT> <?php
			echo $resource->name;
			echo "<br>";
			
	} ?>
		
		<br><br><b>Atividades:</b><br><br>
<?php foreach ($modinfo->instances['assignment'] as $cm) {
		if (!$cm->uservisible) {
			continue;
		}	
		 ?>
			<SELECT style="margin-right:10px; width:120px" NAME="perfilRec[]" required="required">
		<?php
		if (in_array($cm->id, $ativComPerfil)){
			foreach($result4 as $res4){	
				if ($res4->id == $perfilDaAtivID[$cm->id]) {?>		
				<OPTION SELECTED value="<?php echo $cm->id.",".$res4->id; ?>"><?php echo $res4->nome ?></OPTION> <?php }else{ ?>
				<OPTION value="<?php echo $cm->id.",".$res4->id; ?>"><?php echo $res4->nome ?></OPTION><?php				
			}}
		}else{	
			?>
			<OPTION SELECTED></OPTION><?php
			foreach($result4 as $res4){	?>		
				<OPTION value="<?php echo $cm->id.",".$res4->id; ?>"><?php echo $res4->nome ?></OPTION><?php				
			}}?> 
		</SELECT> <?php
			echo $cm->name;
			echo "<br>";		
	}	
		?>
			<br><input type="submit" style="width: 100px;" value="Grava Perfil" />
		</form>

<?php	
	
} else if ($contIniciais < 1){
		?>
	<html>
		<form name="listaRecAtiv" method="post" action="tutor_lista.php?id=<?php echo $id ?>">
	  <b></b>Selecione um recurso e uma atividade para iniciar o estudo:</b><br><br>
	   <b>Recursos:</b><br><br>
		   <?php foreach ($cms as $cm) {
			if (!isset($resources[$cm->modname][$cm->instance])) {
				continue;
			}
			$resource = $resources[$cm->modname][$cm->instance];
			?>
		  <input type="radio" name="recurso" required="required"
			value="<?php echo $cm->id ?>"> <?php echo $resource->name ?><br>
		<?php } ?>
		
		<br><br><b>Atividades:</b><br><br>
		<?php foreach ($modinfo->instances['assignment'] as $cm) {
			if (!$cm->uservisible) {
				continue;
			}	
		 ?>	
			  <input type="radio" name="atividade" required="required"
			value="<?php echo $cm->id ?>"> <?php echo $cm->name ?><br>
			
			
		<?php  }	
		
		foreach ($modinfo->instances['forum'] as $cm) {
			if (!$cm->uservisible) {
				continue;
			}	

			if (($cm->name != 'News forum') && ($cm->name != 'Fórum de notícias')) { ?>	
			  <input type="radio" name="atividade" required="required"
			value="<?php echo $cm->id?>"> <?php echo $cm->name ?><br>
			
			
		<?php  }}	?>
		
			<br><input type="submit" style="width: 100px;" value="Seleciona" />
		</form>

	</html>
	<?php
}else{
?>
<p><b>Recurso e atividade iniciais</b></p>
<?php
$result = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND pre_req_id = ?', array( $id , '0' ));
foreach ($result as $res){
	foreach ($cms as $cm){
		if (($cm->id == $res->rec_ativ_id) and ($recLista < '1')){ // and (!$recu)
			$recLista++;
			echo "Recurso Inicial: ".$cm->name.'<br>';
			$_SESSION['idRecInic'] = $cm->id;
		}
	}
	foreach ($modinfo->instances['assignment'] as $cm) {
		if (($cm->id == $res->rec_ativ_id) and ($ativLista < '1')){ // and (!$ati)
			$ativLista++;
			echo "Atividade Inicial: ".$cm->name.'<br>';
			$_SESSION['idForumInic'] = '';
			$_SESSION['idAtivInic'] = $cm->id;
		}
	}
	foreach ($modinfo->instances['forum'] as $cm) {
		if (($cm->id == $res->rec_ativ_id) and ($forLista < '1')){ // and (!$ati)
			$forLista++;
			$_SESSION['idAtivInic'] = '';
			echo "Atividade Inicial: ".$cm->name.'<br>';
			$_SESSION['idForumInic'] = $cm->id;
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
	foreach ($modinfo->instances['assignment'] as $cm) {
		if ($cm->id == $res1->rec_ativ_id){ 
			$arrAtiv[$res1->rec_ativ_id]++; 			
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
<script language="JavaScript">
   function Selecionar()
   {
     document.listaRecAtiv.action="tutor_depend.php?id=<?php echo $id ?>";
     document.forms.listaRecAtiv.submit();
   }
  </script>
  <script language="JavaScript">
   function VerGrafo()
   {
     document.listaRecAtiv.action="tutor_arvore.php?id=<?php echo $id ?>";
     document.forms.listaRecAtiv.submit();
  }
 </script>
<form name="listaRecAtiv" method="post">
	<p>Selecione um recurso ou uma atividade para informar seus pré-requisitos</p>
	<p><b>Recursos e atividades:</b></p>
   <SELECT style="margin-right:15px; width:120px" NAME="recAtiv" required="required">
			<OPTION SELECTED></OPTION>
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
	
	<?php foreach ($modinfo->instances['assignment'] as $cm) {
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
	}		
	
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
	</SELECT>
		<br><br><input type="button"  style="width: 100px;" value="Seleciona" onClick="Selecionar()"/>
		<input type="button" style="margin-left:65px; width: 200px;" value="Ver Grafo Pré-requisitos" onClick="VerGrafo()"/>
	</form>
</html>
<?php

}


echo $OUTPUT->footer();


