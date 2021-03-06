<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class wap_theme_tmpl_fssave
{

    public function get_default($type, $theme)
    {
        return app::get('wap')->getConf('custom_template_'.$theme.'_'.$type);
    }//End Function

    public function set_default($type, $theme, $value)
    {
        return app::get('wap')->setConf('custom_template_'.$theme.'_'.$type, $value);
    }//End Function

    public function del_default($type, $theme)
    {
        return app::get('wap')->setConf('custom_template_'.$theme.'_'.$type, '');
    }//End Function

    public function set_all_tmpl_file($theme)
    {
        $row = app::get('wap')->model('themes_tmpl')->select()->columns('tmpl_path')->where('theme = ?', $theme)->instance()->fetch_col();
        return app::get('wap')->setConf('custom_template_'.$theme.'_all_tmpl', $row['tmpl_path']);
    }//End Function

    public function get_all_tmpl_file($theme)
    {
        return app::get('wap')->getConf('custom_template_'.$theme.'_all_tmpl');
    }//End Function

    public function tmpl_file_exists($tmpl_file, $theme)
    {
        $all = $this->get_all_tmpl_file($theme);
        $all[] = 'block/header.html';
        $all[] = 'block/footer.html';   //头尾文件
        if(is_array($all)){
            return in_array($tmpl_file, $all);
        }else{
            return false;
        }
    }//End Function

    public function get_edit_list($theme)
    {
        $data = app::get('wap')->model('themes_tmpl')->getList('*', array("theme"=>$theme));
        if(is_array($data)){
            foreach($data AS $value){
                if($this->get_default($value['tmpl_type'], $theme) == $value['tmpl_path'])
                    $value['default'] = 1;

                $ret[$value['tmpl_type']][] = $value;
            }
        }
        return $ret;
    }//End Function

    public function install($theme)
    {
        $list = array();
        $this->__get_all_files(WAP_THEME_DIR . '/' . $theme, $list, false);
        if(file_exists(WAP_THEME_DIR.'/'.$theme.'/cart.html')){
            if(!file_exists(WAP_THEME_DIR.'/'.$theme.'/order_detail.html')){
                copy(WAP_THEME_DIR.'/'.$theme.'/cart.html',WAP_THEME_DIR.'/'.$theme.'/order_detail.html');
            }
            if(!file_exists(THEME_DIR.'/'.$theme.'/order_index.html')){
                copy(WAP_THEME_DIR.'/'.$theme.'/cart.html',WAP_THEME_DIR.'/'.$theme.'/order_index.html');
            }
        }
        $ctl = $this->get_name();
        foreach($list AS $key=>$value){
            $file_name = basename($value, '.html');
            if(!strpos($file_name,'.')){
                if(($pos=strpos($file_name,'-'))){
                    $type=substr($file_name,0,$pos);
                    $file[$type][$key]['name']=$ctl[substr($file_name,0,$pos)];
                    $file[$type][$key]['file']=$file_name.'.html';
                }else{
                    $type=$file_name;
                    $file[$file_name][$key]['name']=$ctl[$file_name];
                    $file[$file_name][$key]['file']=$file_name.'.html';
                    //$file[$key]['name']=$ctl[$file_name];
                }

                touch(WAP_THEME_DIR . '/' . $theme . '/' . $file_name . '.html');

                if($type && array_key_exists($type, $ctl)){
                    $array = array(
                        'theme'     => $theme,
                        'tmpl_type' => $type,
                        'tmpl_name' => $file_name.'.html',
                        'tmpl_path' => $file_name.'.html',
                        'version'   => filemtime(WAP_THEME_DIR . '/' . $theme . '/' . $file_name . '.html'),
                        'content'   => file_get_contents(WAP_THEME_DIR . '/' . $theme . '/' . $file_name . '.html')
                    );
                    $themes_file_data = array(
                        'filename' => $array['tmpl_path'],
                        'filetype' => 'html',
                        'fileuri'  => $array['theme'] . ':' . $array['tmpl_path'],
                        'version'  => $array['version'],
                        'theme'    => $array['theme'],
                        'memo'     => '模板文件',
                        'content'  => $array['content']
                    );
                    //fisrt, insert tmpl_file info to sdb_wap_themes_file
                    //return the file's file_id_
                    if($file_id = $this->insert_themes_file($themes_file_data)){
                        $array['rel_file_id'] = $file_id;
                    }

                    $this->insert($array);
                    if(!$this->get_default($type, $theme)){
                        $this->set_default($type, $theme, $file_name.'.html');
                    }
                }
            }
        }
    }//End Function

    //
    public function insert_themes_file($data){
        if($file_id = app::get('wap')->model('themes_file')->insert($data)){
            return $file_id;
        }else{
            return false;
        }
    }

    public function insert($data)
    {
        if(app::get('wap')->model('themes_tmpl')->insert($data)){
            $this->set_all_tmpl_file($data['theme']);
            return true;
        }else{
            return false;
        }
    }//End Function

    public function insert_tmpl($data)
    {
        $dir = WAP_THEME_DIR . '/' . $data['theme'];
        if(!is_dir($dir))   return false;
        if(empty($data['tmpl_type']) || empty($data['content']))    return false;
        $data['tmpl_path'] = strtolower(preg_replace('/[^a-z0-9]/', '', $data['tmpl_path']));
        if($data['tmpl_path']){
            $target = $dir . '/' . $data['tmpl_type'] . '-' . $data['tmpl_path'] . '.html';
            if(is_file($target)){
                $target = '';
            }
        }
        if(empty($target)){
            $flag = true;
            for($i=1; $flag; $i++){
                $target = sprintf('%s/%s-(%s).html', $dir, $data['tmpl_type'], $i);
                if(file_exists($target))    continue;
                $flag = false;
            }
        }
        if(file_put_contents($target, $data['content'])){
            $data['tmpl_path'] = basename($target);
            $data['tmpl_name'] = ($data['tmpl_name']) ? $data['tmpl_name'] : basename($target);
            $data['version'] = filemtime($target);
            $themes_file_data = array(
                'filename' => $data['tmpl_path'],
                'filetype' => 'html',
                'fileuri'  => $data['theme'] . ':' . $data['tmpl_path'],
                'version'  => $data['version'],
                'theme'    => $data['theme'],
                'memo'     => '模板文件',
                'content'  => $data['content']
            );
            //先插入themes_file表，返回file_id
            if($file_id = $this->insert_themes_file($themes_file_data)){
                $data['rel_file_id'] = $file_id;
            }
            return $this->insert($data);
        }
        return false;
    }//End Function

    public function copy_tmpl($tmpl, $theme)
    {
        $source = WAP_THEME_DIR . '/' . $theme . '/' . $tmpl;
        if(!is_file($source))   return false;
        $data = app::get('wap')->model('themes_tmpl')->getList('*', array('theme'=>$theme, 'tmpl_path'=>$tmpl));
        $data = $data[0];
        if(empty($data))    return false;
        $flag = true;
        for($i=1; $flag; $i++){
            $target = sprintf('%s/%s/%s-(%s).html', WAP_THEME_DIR, $theme, $data['tmpl_type'], $i);
            if(file_exists($target))    continue;
            copy($source, $target);
            $flag = false;
        }
        unset($data['id']);
        $data['tmpl_path'] = basename($target);
        $data['tmpl_name'] = basename($target);
        if($this->insert($data)){
            $widgets = app::get('wap')->model('widgets_instance')->getList('*', array('core_file'=>$theme.'/'.$tmpl));
            foreach($widgets AS $widget){
                unset($widget['widgets_id']);
                $widget['core_file'] = $theme . '/' . basename($target);
                $widget['modified'] = time();
                app::get('wap')->model('widgets_instance')->insert($widget);
            }
            return true;
        }else{
            return false;
        }
    }//End Function

    public function delete_tmpl_by_theme()
    {
        //不删除实体文件，只处理数据库和conf
        $datas = app::get('wap')->model('themes_tmpl')->getList('tmpl_path', array('theme'=>$theme));
        foreach($datas AS $data){
            $this->delete_tmpl($data['tmpl_path'], $theme);
        }
    }//End Function

    public function delete_tmpl($tmpl, $theme)
    {
        $source = WAP_THEME_DIR . '/' . $theme . '/' . $tmpl;
        if(!is_file($source))   return false;
        $data = app::get('wap')->model('themes_tmpl')->getList('*', array('theme'=>$theme, 'tmpl_path'=>$tmpl));
        if($data[0]['id'] > 0){
            if(app::get('wap')->model('themes_tmpl')->delete(array('id'=>$data[0]['id']))){
                //删除模板文件的同时删除themes_file的对应文件
                app::get('wap')->model('themes_file')->delete(array('theme'=>$theme,'filename'=>$tmpl));
                app::get('wap')->model('widgets_instance')->delete(array('core_file'=>$theme.'/'.$tmpl));
                $this->set_all_tmpl_file($data[0]['theme']);
                if($this->get_default($data[0]['tmpl_type'], $theme) == $data[0]['tmpl_path']){
                    $this->del_default($data[0]['tmpl_type'], $theme);
                }
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }//End Function

    private function __get_all_files($sDir, &$aFile, $loop=true){
        if($rHandle=opendir($sDir)){
            while(false!==($sItem=readdir($rHandle))){
                if ($sItem!='.' && $sItem!='..' && $sItem!='' && $sItem!='.svn' && $sItem!='_svn'){
                    if(is_dir($sDir.'/'.$sItem)){
                        if($loop){
                            $this->__get_all_files($sDir.'/'.$sItem,$aFile);
                        }
                    }else{
                        $aFile[]=$sDir.'/'.$sItem;
                    }
                }
            }
            closedir($rHandle);
        }
    }

    public function get_name(){
        $ctl = $this->__get_tmpl_list();
        return $ctl;
    }


    public function get_list_name($name)
    {
        $name = rtrim(strtolower($name),'.html');
        $ctl = $this->__get_tmpl_list();
        return $ctl[$name];
    }//End Function

    private function __get_tmpl_list() {
        $ctl = array(
            'index'=>app::get('wap')->_('首页'),
            'gallery'=>app::get('wap')->_('商品列表页'),
            'product'=>app::get('wap')->_('商品详细页'),
            'article'=>app::get('wap')->_('文章页'),
            'articlelist'=>app::get('wap')->_('文章列表页'),
            'brandlist'=>app::get('wap')->_('品牌专区页'),
            'brand'=>app::get('wap')->_('品牌商品展示页'),
            'cart'=>app::get('wap')->_('购物车页'),
            'passport'=>app::get('wap')->_('注册/登录页'),
            'member'=>app::get('wap')->_('会员中心页'),
            'order_detail'=>app::get('wap')->_('订单详细页'),
            'order_index'=>app::get('wap')->_('订单确认页'),
            'splash'=>app::get('wap')->_('信息提示页'),
            'default'=>app::get('wap')->_('默认页'),
            'activity'=>app::get('wap')->_('活动页'),
            'pos'=>app::get('wap')->_('pos页面'),
        );
        return $ctl;
    }

    public function touch_theme_tmpl($theme)
    {
        $rows = app::get('wap')->model('themes_tmpl')->select()->columns('tmpl_path')->where('theme = ?', $theme)->instance()->fetch_all();
        if($rows){
            array_push($rows, array('tmpl_path'=>'block/header.html'), array('tmpl_path'=>'block/footer.html'));
            foreach($rows AS $row){
                $this->touch_tmpl_file($theme . '/' . $row['tmpl_path']);
            }
            kernel::single('wap_theme_base')->set_theme_cache_version($theme);
        }

        $cache_keys = kernel::database()->select('SELECT `prefix`, `key` FROM sdb_base_kvstore WHERE `prefix` IN ("cache/template", "cache/theme")');
        foreach($cache_keys as $value)
        {
            base_kvstore::instance($value['prefix'])->get_controller()->delete($value['key']);
        }
        kernel::database()->exec('DELETE FROM sdb_base_kvstore WHERE `prefix` IN ("cache/template", "cache/theme")');

        cachemgr::init(true);
        cachemgr::clean($msg);
        cachemgr::init(false);

        return true;
    }//End Function


    public function touch_tmpl_file($tmpl, $time=null)
    {
        if(empty($time))    $time = time();
        $source = WAP_THEME_DIR . '/' . $tmpl;
        if(is_file($source)){
            return @touch($source, $time);
        }else{
            return false;
        }
    }//End Function

    function output_pkg($theme){
        $tar = kernel::single('base_tar');
        $workdir = getcwd();

        if(chdir(WAP_THEME_DIR.'/'.$theme)){
            $this->__get_all_files('.',$aFile);
            for($i=0;$i<count($aFile);$i++){
                if($f = substr($aFile[$i],2)){
                    if($f!='theme.xml'){
                        $tar->addFile($f);
                    }
                }
            }
            if(is_file('info.xml')){
                $tar->addFile('info.xml',file_get_contents('info.xml'));
            }
            $tar->addFile('theme.xml',$this->make_configfile($theme));

            $aTheme = kernel::single('wap_theme_base')->get_theme_info($theme);

            kernel::single('base_session')->close();

            $name = kernel::single('base_charset')->utf2local(preg_replace('/\s/','-',$aTheme['name'].'-'.$aTheme['version']),'zh');
            @set_time_limit(0);

            header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header('Content-type: application/octet-stream');
            header('Content-type: application/force-download');
            header('Content-Disposition: attachment; filename="'.$name.'.tgz"');
            $tar->getTar('output');
            chdir($workdir);
        }else{
            chdir($workdir);
            return false;
        }
    }

    public function make_configfile($theme){
        $aTheme = kernel::single('wap_theme_base')->get_theme_info($theme);

       $aWidget['widgets'] = app::get('wap')->model('widgets_instance')->select()->where("core_file LIKE '".$theme."/%'")->instance()->fetch_all();
        foreach($aWidget['widgets'] as $i => &$widget){
            $widget['core_file'] = str_replace($theme.'/', '', $widget['core_file']);
            $widget['params'] = serialize($widget['params']);
        }

        $aTheme['id'] = $aTheme['theme'];
        $aTheme=array_merge($aTheme, $aWidget);

        $render = kernel::single('base_render');
        $render->pagedata = $aTheme;
        $sXML = $render->fetch('admin/theme/theme.xml', 'wap');

        return $sXML;
    }

}//End Class
