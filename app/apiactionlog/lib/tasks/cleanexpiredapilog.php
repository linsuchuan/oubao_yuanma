<?php
class apiactionlog_tasks_cleanexpiredapilog extends base_task_abstract implements base_interface_task{

    public function exec($params=null){
        logger::info('111111');
        kernel::single('apiactionlog_command_cleandata')->command_cleandata();
    }

}
