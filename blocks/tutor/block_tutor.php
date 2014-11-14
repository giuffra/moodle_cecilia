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
 
}   // Here's the closing bracket for the class definition 

   