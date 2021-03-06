<?php namespace beijing;
/**
 * @description 北京房主儿 合租房抓取规则
 * @classname 北京房主儿
 */
include_once ('simple_html_dom.php');
class FangzhuHezu extends \city\PublicClass{
    Public function house_page() {
        $maxPage =  1081;
        $url = "http://bj.fangzhur.com/iosapp/highsearch3.0.php";
        $urlarr = array();
        for($page = 1; $page < $maxPage; $page ++) {
            $urlarr[] = $url."|".$page;
        }
        return $urlarr;
    }
    /*
     * 获取列表页
     */
    Public function house_list($url){
        $house_info = array();
        $source_url = explode("|",$url)[0];
        $page = explode("|",$url)[1];
        $Parameters = array ();
        //1 整租  2出售  3合租
//        $Parameters ["house_way"] = "3";
//        //$Parameters ["order"] = "";
//        $Parameters ["page"] = $page;
        $Parameters['handle'] = 'array';
        $Parameters['post'] = ['house_way'=>3,'page'=>$page,'member_id'=>1709200];
        $list_decode =$this->getUrlContent(  $source_url, $Parameters );
        $house_info = array();
        foreach ( $list_decode ['xinxi'] as $value ) {
            $house_info[] = "http://bj.fangzhur.com/hezu/d-".$value['id'].".html";
        }
        //var_dump($house_info); exit;
        return $house_info;
    }
    /*
     * 获取详情
     */
    public function house_detail($source_url){
        //下架检测-跳转下架
        $house_info['off_type'] = $this->is_off($source_url);
        preg_match('/hezu\/d\-([\x{0000}-\x{ffff}]*?)\.htm/u',$source_url,$value);
        $value = $value[1];
        $url_content = "http://bj.fangzhur.com/iosapp/rent/rentdetail.php";
        // $Parameters这个是要提交的数组
//        $Parameters = array ();
//        $Parameters ["house_id"] = $value;
//        $Parameters ["member_id"] = "-1";
        $Parameters['post'] = ['house_id'=>$value,'member_id'=>'1709200'];
        $Parameters['handle'] = 'array';
        $getparam = "house_id=".$value."&member_id=1709200";
        $result_content = $this->getUrlContent(  $url_content, $Parameters );
        //标题
        //$house_info['house_title'] = $list_decode['xinxi'][$key]['house_title'];
        $house_info['house_title'] = $result_content['rentxiangqing'][0]['borough_name'];
        //城区
        //通过API抓取城区商圈
        //抓取经纬度
       // var_dump($result_content['rentxiangqing'][0]);die;
        $house_info['cityarea_id'] = $result_content['rentxiangqing'][0]['cityarea_name'];
        //商圈
        $house_info['cityarea2_id']= $result_content['rentxiangqing'][0]['cityarea2_name'];
        //小区名
        $house_info['borough_name']= $result_content['rentxiangqing'][0]['borough_name'];
        //小区ID
        $house_info['borough_id']  = "";
        //出租间面积
       // $house_info['house_totalarea']= $result_content['rentxiangqing'][0]['house_totalarea'];
        //合租间的面积
        $house_info['house_room_totalarea']= $result_content['rentxiangqing'][0]['house_totalarea'];
        //朝向
        $house_info['house_toward']= $result_content['rentxiangqing'][0]['house_toward'];
        //卧室/居
        $house_info['house_room']= $result_content['rentxiangqing'][0]['house_room'];
        //厅
        $house_info['house_hall']= $result_content['rentxiangqing'][0]['house_hall'];
        //卫生间
        $house_info['house_toilet']= "";
        //厨房
        $house_info['house_kitchen'] = "";
        //装修情况
        $house_info['house_fitment']= $result_content['rentxiangqing'][0]['house_fitment'];
        //房源类型
        $house_info['house_type']= $result_content['rentxiangqing'][0]['house_type'];
        //所在楼层
        $house_info['house_floor']= $result_content['rentxiangqing'][0]['house_floor'];
        //总楼层
        $house_info['house_topfloor'] = $result_content['rentxiangqing'][0]['house_topfloor'];
        //联系人姓名
        $house_info['owner_name']= $result_content['rentxiangqing'][0]['owner_name'];
        //联系人电话
        $house_info['owner_phone']= $result_content['rentxiangqing'][0]['owner_phone'];
        //房源描述
        $house_info['house_desc']= $result_content['rentxiangqing'][0]['house_desc'];
        //室内图  用|分割
        //	 			$house_info['house_pic_unit']= $result_content['rentxiangqing'][0]['house_thumb'];
        //户型图
        $house_info['house_pic_layout'] = "";
        //卧室类型 主卧 还是次卧
        $house_info['house_style']= "";
        //是否转租 1.转租  2.非转租
        $house_info['house_relet']="";
        //来源
        $house_info['source']= 10;
        $house_info['source_owner'] = 1;

        //appurl
        $house_info['app_url']= "http://bj.fangzhur.com/iosapp/rent/rentdetail.php?".$getparam;
        //wap端url
        $house_info['wap_url']= "";
        //电脑端url
        $house_info['source_url']= "http://bj.fangzhur.com/hezu/d-".$value.".html";

        $html = gb2312_to_utf8(getSnoopy($house_info['source_url']));
        preg_match("/id=\"List1[\x{0000}-\x{ffff}]+?<\/ul>/u", $html, $pic_tags);
        preg_match_all("/src=\"(\S+?)\"/", $pic_tags[0], $pics);

        $html = gb2312_to_utf8(getSnoopy($source_url));
        preg_match("/id=\"List1[\x{0000}-\x{ffff}]+?<\/ul>/u", $html, $pic_tags);
        preg_match_all("/src=\"(\S+?)\"/", $pic_tags[0], $pics);
        if(!empty($pics[0])){
            $house_info['house_pic_unit'] = array();
            foreach($pics[1] as $k=>$v){
                $v = str_replace('_thumb','',$v);
                $house_info['house_pic_unit'][] = $v;
            }
            $house_info['house_pic_unit'] = array_unique($house_info ['house_pic_unit']);
            $house_info['house_pic_unit'] = implode('|', $house_info['house_pic_unit']);
        }else{
            $house_info['house_pic_unit'] = "";
        }

        //入住人限制
        $house_info['sex']= "";
        //入住时间
        $house_info['into_house']= "";
        //付款方式 例如信用卡
        //	 			$house_info['pay_method']= $result_content['rentxiangqing'][0]['fukuan_type'];
        $house_info['pay_method']= "";
        //付款类型 例如 押一付三
        $house_info['pay_type']= "";
        //标签(房源特色)
        $house_info['tag']= "";
        //房源评价
        $house_info['comment']= "";
        //房源编号
        $house_info['house_number'] = $result_content['rentxiangqing'][0]['house_no'];
        //押金
        //	 			$house_info['deposit']= $result_content['rentxiangqing'][0]['house_deposit'];
        $house_info['deposit']= "";
        //合租户数
        $house_info['homes']= "";
        //真实度
        $house_info['is_ture']= "";
        //室友信息
        $house_info['friend_info']= "";
        //价格
        $house_info['house_price']= $result_content['rentxiangqing'][0]['house_price'];
        //房屋年龄
        $house_info['house_age']= $result_content['rentxiangqing'][0]['house_age'];
        //创建时间
        $house_info['created']= time();
        //更新时间
        $house_info['house_configroom'] ="";
        $house_info['house_configpub'] ="";
        $house_info['updated']= time();
        //$house_info['pub_time']= $result_content['rentxiangqing'][0]['updated'];
        $house_info['is_contrast'] = 2;
        $house_info['is_fill'] = 2;
        return $house_info;
    }
    	
	//统计官网数据
	public function house_count(){
	    	    $PRE_URL = 'http://bj.fangzhur.com/hezu/';
	    	    $totalNum = $this->queryList($PRE_URL, [
	    	        'total' => ['.result > span:nth-child(1)','text'],
	    	    ]);
	    	    return $totalNum;
// 	    return 0;
	}
    public function is_off($url,$html=''){
        return 2;
        if(!empty($url)){
            if(empty($html)){
                $html = $this->getUrlContent($url);
            }
            //抓取下架标识
            $Tag = \QL\QueryList::Query($html,[
//                "isOff" => ['div.btn','text','',function($item){
//                    return preg_match("/成交/",$item);
//                }],
                "borough_name"=>['.xiang4212 > ul:nth-child(3) > li:nth-child(4) > a:nth-child(1)','text'],
                "house_price"=>['.xiang4212 > span:nth-child(1) > strong:nth-child(1)','text',''],
                "house_totalarea"=>['.xiang4212 > ul:nth-child(2) > li:nth-child(2)','text'],
            ])->getData(function($item){
                return $item;
            });
            foreach ($Tag[0] as $key=>$value) {
//                if($key == "isOff" && $value == 1){
//                    return 1;
//                }else{
//                    return 2;
//                }
                return 2;
            }
            return 1;
        }
    }
}
