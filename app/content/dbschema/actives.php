<?php

$db['actives'] = array (
    'columns' =>
    array (
        'active_id' =>array (
            'type' => 'number',
            'required' => true,
            'label'=> app::get('content')->_('活动ID'),
            'pkey' => true,
            'extra' => 'auto_increment',
            'width' => 50,
            'order' => 10,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'title' =>array (
            'type' => 'varchar(200)',
            'required' => true,
            'label' => app::get('content')->_('活动名称'),
            'width' => 200,
            'order' => 20,
            'searchtype' => 'has',
            'editable' => true,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'is_title'=>true,
        ),
        'tmpl_path' =>array (
            'type' => 'varchar(50)',
            'label'=> app::get('content')->_('活动页模板'),
            'editable' => false,
            'width' => 200,
            'order' => 30,
            'searchtype' => 'has',
            'editable' => true,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'is_title'=>true,
        ),
        'seo_title'=>array (
            'type' => 'varchar(100)',
            'label' => app::get('content')->_('SEO标题'),
            'editable' => true,
        ), 
        'seo_description' =>array(
            'type' => 'mediumtext',
            'label' => app::get('content')->_('SEO简介'),
            'editable' => true,
        ),
        'seo_keywords' =>array(
            'type' => 'varchar(200)',
            'label' => app::get('content')->_('SEO关键字'),
            'editable' => true,
        ),
        'ifpub' => array(
            'type' => 'bool',
            'required' => true,
            'default' => 'false',
            'label' => app::get('content')->_('发布'),
            'editable' => true,
            'in_list' => true,
            'filtertype' => 'yes',
            'filterdefault' => false,
            'width' => 40,
            'order' => 60,
            'default_in_list' => true,
        ),
        'disabled' => array(
            'type' => 'bool',
            'required' => true,
            'default' => 'false',
            'editable' => true,
        ),
  ),
    'version' => '$Rev$',
    'comment' => app::get('content')->_('活动表'),
);
?>