<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


/**
 * b2c order interactor with center
 * shopex team
 * dev@shopex.cn
 */
class b2c_apiv_apis_response_goods_store
{
    /**
     * app object
     */
    public $app;

    /**
     * 构造方法
     * @param object app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->objMath = kernel::single("ectools_math");
    }


    /**
     * 库存修改
     * @param array sdf
     * @return boolean success of failure
     */
    public function updateStore(&$sdf, $thisObj)
    {
        $error_bn = array();
        $true_bn = array();

        if(!isset($sdf['list_quantity']) || !$sdf['list_quantity']){
            $thisObj->send_user_error(app::get('b2c')->_('需要更新的货品的库存不存在！'), array());
        }else{
            $has_error = false;
            $arr_store = json_decode($sdf['list_quantity'], true);

            $product = $this->app->model('products');
            $obj_goods = $this->app->model('goods');
            $fail_products = array();
            $db = kernel::database();
            if (isset($arr_store) && $arr_store)
            {
                foreach ($arr_store as $arr_product_info)
                {
                    if ($arr_product_info['bn'] && is_numeric($arr_product_info['quantity']))
                    {
                        $arr_product = $product->dump(array('bn' => $arr_product_info['bn']));
                        if ($arr_product)
                        {
                            $store_increased = $this->objMath->number_minus(array(floatval($arr_product_info['quantity']), floatval($arr_product['store'])));
                            $arr_goods = $db->selectrow('SELECT store,goods_id from sdb_b2c_goods where goods_id ='.$arr_product['goods_id']);


                            $goods_store = $this->objMath->number_plus(array($arr_goods['store'],$store_increased,$arr_product['freez']));
                            $arr_goods['store'] = ($goods_store == '0') ? 0:$goods_store;
                            $arr_product['store'] = $this->objMath->number_plus(array($arr_product_info['quantity'],$arr_product['freez']));
                            $arr_product['last_modify'] = time();
                            $storage_enable = $this->app->getConf('site.storage.enabled');
                            if (!is_null($arr_product['store']) && $storage_enable != 'true'){
                                $is_save = $product->save($arr_product);
                                if($is_save){
                                    $obj_goods->update($arr_goods, array('goods_id' => $arr_goods['goods_id']));
                                }
                            }else{
                                $is_save = true;
                            }

                            if( ! $is_save){
                                $msg = $this->app->_('商品库存更新失败！');
                                $has_error = true;

                                $fail_products[] = $arr_product_info['bn'];
                                $error_bn[] = $arr_product_info['bn'];
                                continue;
                            }else{
                                $true_bn[] = $arr_product_info['bn'];
                            }
                        }else{
                            $has_error = true;
                            $fail_products[] = $arr_product_info['bn'];
                            $error_bn[] = $arr_product_info['bn'];
                            continue;
                        }
                    }else{
                        $has_error = true;
                        continue;
                    }
                }

                if( ! $has_error){
                    $data = array('error_bn'=>$error_bn, 'true_bn'=>$true_bn);
                    return $data;
                    //return true;
                }else{
                    // 更新部分失败.
                    $data = array('error_bn'=>$error_bn, 'true_bn'=>$true_bn);
                    $fail_products = array('error_response' => $fail_products);
                    $thisObj->send_user_error(app::get('b2c')->_('更新库存部分失败！'), $data);
                }
            }else{
                $thisObj->send_user_error(app::get('b2c')->_('更新的商品的库存信息不存在！'), array());
            }
        }
    }

    /**
     * 冻结库存请0
     * @param array sdf
     * @return boolean success of failure
    */
    public function updateFreezStore(&$sdf, $thisObj)
    {

        if (!isset($sdf['order_bn']) || !$sdf['order_bn'])
        {
            trigger_error(app::get('b2c')->_('订单标号不存在！'), E_USER_ERROR);
        }
        else
        {
            $obj_orders = $this->app->model('orders');
            $goods = $this->app->model('goods');
            $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
            $sdf_order = $obj_orders->dump($sdf['order_bn'], '*', $subsdf);
            $stock_freez_time = $this->app->getConf('system.goods.freez.time');

            if ($stock_freez_time == '1')
            {
                // 清除预占库存
                if ($sdf_order['order_objects'])
                {
                    foreach ($sdf_order['order_objects'] as $arr_sdf_objs)
                    {
                        if ($arr_sdf_objs['order_items'])
                        {
                            foreach ($arr_sdf_objs['order_items'] as $arr_sdf_items)
                            {
                                $goods->unfreez($arr_sdf_items['products']['goods_id'], $arr_sdf_items['products']['product_id'], $arr_sdf_items['quantity']);
                            }
                        }
                    }
                }
            }

            return array('tid'=>$sdf['order_bn']);
        }
    }
}
