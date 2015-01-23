<?php

class block_tutor extends block_list {
    public function init() {
        $this->title = get_string('tutor', 'block_tutor');
    }
	
public function get_content() {
    if ($this->content !== null) {
      return $this->content;
    }	
    $this->content         =  new stdClass;
  //  $this->content->text   = 'Ativar Tutor';
    //$this->content->footer = 'Footer here...';
	$this->content->items  = array();
	$id = required_param('id', PARAM_INT);	
//	$id = 2;
//	$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
//	echo $id;
//	$id1 = $course->id;
//	echo $id1;

	$this->content->items[] = html_writer::tag('a', 'Configurar tutor', array('href' => '../blocks/tutor/tutor_form.php?id='.$id));
//	$this->content->items[] = html_writer::link('../blocks/tutor/appletAwt.html', 'Ativar Tutor', array('target' => '_blank'));
 
    return $this->content;
  }

  public function specialization() {
  if (!empty($this->config->title)) {
    $this->title = $this->config->title;
  } else {
    $this->config->title = 'Tutor';
  }
 
  if (empty($this->config->text)) {
  }    
    $this->config->text = 'Texto tutor';
}  

public function applicable_formats() {
  return array(
           'course-view' => true, 
    'course-view-social' => false);
}
/*
public function retorna_pais($filhos){

echo "<br> id do curso ".$course->id."<br>";
$arrayPais = array();
$resultPais = array();
$x = 2;
echo "filho array: ".$filhos."<br>";
	foreach ($filhos as $f){
	echo $f;
		//echo "id". $id."<br>";
			$resultPais = $DB->get_records_sql('SELECT * FROM {tutor_dependencia} WHERE curso_id = ? AND rec_ativ_id = ?', array($x , $f));
			echo "aaaaaaaaaaaaaaaaaaaaaaaaaaaaa ".$resultPais;
			
			foreach ($resultPais as $resPais){
			echo "entrando no segundo foreach <br>";
				$arrayPais = $resPais->pre_req_id;
			}
		}	
	if (count($filhos) == 0){
	echo "estou no if <br>";
		return $arrayPais;
	}else{		
		return this->array_merge($arrayPais, retorna_pais($arrayPais));
	}
}*/
 
}   // Here's the closing bracket for the class definition 

   