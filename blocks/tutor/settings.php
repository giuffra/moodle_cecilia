<?php
$settings->add(new admin_setting_heading(
            'headerconfig',
            get_string('headerconfig', 'block_tutor'),
            get_string('descconfig', 'block_tutor')
        ));
 
$settings->add(new admin_setting_configcheckbox(
            'tutor/Allow_HTML',
            get_string('labelallowhtml', 'block_tutor'),
            get_string('descallowhtml', 'block_tutor'),
            '0'
        ));