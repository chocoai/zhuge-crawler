<?php namespace suzhou;
/**
 * @description 苏州房多多二手房抓取规则
 * @classname 苏州房多多（k-ok）
 */

Class Fdd  extends \city\PublicClass
{
    public $PRE_URL = 'http://esf.fangdd.com/suzhou';
    /*
    * 抓取
    */
    Public function house_page(){
        $urlList = $this->get_condition($this->PRE_URL);
        $num = count($urlList);
        $urlarr = [];
        for($n=0;$n<$num;$n++){
            //从当前条件首页抓取最大页
            $maxPage = $this->get_maxPage($urlList[$n])[0];
            for ($page=1; $page<=intval($maxPage); $page++){
                $urlarr[] = $urlList[$n].'_pa'.$page;
            }
        }
        return $urlarr;
    }
    
    /*
     * 获取列表页
    * @param	dis string 分城区抓取设置为城区信息
    */
    public function house_list($url){
        $html=$this->getUrlContent($url);
        $house_info = \QL\QueryList::Query($html,[
            //获取单个房源url
            'link' => ['div.main.clearfix > div.house-info.pull-left > div.list-item.clearfix > div.bg_color.clearfix > div.content.pull-left > div.name-title.clearfix > a', 'href', '', function($u){
                return $u;
            }],
        ])->getData(function($item){
            return $item['link'];
        });
        return $house_info;
    }
    
    /*
     * 获取详情
    */
    public function house_detail($source_url){
        $html = $this->getUrlContent($source_url);
        $houseInfo = [];
        \QL\QueryList::Query($html,[
            'house_title' => ['.house__name', 'text'],
            'borough_name' => ['.house__name > .tit:eq(0)', 'text'],
            'house_totalarea' => ['.house__name > .tit:eq(2)', 'text'],
            'house_price' => ['span[class="number font"]', 'text'],
            'cityarea_id' => ['.address > a:eq(0) ', 'text'],
            'cityarea2_id' => ['.address > a:eq(1) ', 'text'],
            'floor' => ['.padding__top.static__td:eq(1)','text','-.title'],
            'home_detail' => ['.house__detail', 'text',''],
            'owner_name' => ['.ownername', 'text'],
            'house_pic_unit' => ['.thumbnail__item__container > img', 'src'],
            'house_desc' => ['#owner-say-content', 'text', '-span'],

        ])->getData(function($data)use(&$houseInfo){
            isset($data['borough_name']) && $houseInfo['borough_name'] = $data['borough_name'];
            isset($data['house_price']) && $houseInfo['house_price'] = $data['house_price'];
            isset($data['cityarea_id']) && $houseInfo['cityarea_id'] = $data['cityarea_id'];
            isset($data['cityarea2_id']) && $houseInfo['cityarea2_id'] = $data['cityarea2_id'];
            if(isset($data['house_title'])){
                $houseInfo['house_title'] = preg_replace( '/[\r\n\s\t]/', '', $data['house_title']);
            }
            if($data['house_totalarea']){
                preg_match('/^(\d+(\.\d+)?)/', trim($data['house_totalarea']), $match);
                $houseInfo['house_totalarea'] = $match[1];
            }
            if($data['floor']){
                $floor = explode('/', $data['floor']);
                $houseInfo['house_floor'] = $floor[0];
                $houseInfo['house_topfloor'] = $floor[1];
            }
            
            if($data['home_detail']){
                $houseInfo['house_unitprice'] = preg_match('/单价：(\d+)/', $data['home_detail'],$homedetail)?$homedetail[1]:'';
                $houseInfo['house_room'] = preg_match('/(\d+)室/', $data['home_detail'],$homedetail)?$homedetail[1]:0;
                $houseInfo['house_hall'] = preg_match('/(\d+)厅/', $data['home_detail'],$homedetail)?$homedetail[1]:0;
                $houseInfo['house_toilet'] = preg_match('/(\d+)卫/', $data['home_detail'],$homedetail)?$homedetail[1]:0;
                $houseInfo['house_built_year'] = preg_match('/竣工时间：(\d{4})年/', $data['home_detail'],$homedetail)?$homedetail[1]:'';
                $houseInfo['house_number'] = preg_match('/房源编号：(\w+)/', $data['home_detail'],$homedetail)?$homedetail[1]:'';
                $houseInfo['house_toward'] = preg_match('/房屋朝向：(\S+)/', $data['home_detail'],$homedetail)?str_replace('--','',$homedetail[1]):'';
                $houseInfo['house_type'] = preg_match('/房型：(\S+)/', $data['home_detail'],$homedetail)?str_replace('--','',$homedetail[1]):'';

            }



            if(isset($data['owner_name'])){
                $houseInfo['owner_name'] =  ltrim(preg_replace( '/[\r\n\s\t]/', '', $data['owner_name']), '业主');
            }

            $data['house_pic_unit'] && $houseInfo['house_pic_unit'][] =  $data['house_pic_unit'];
            $data['house_desc'] && $houseInfo['house_desc'] =  $data['house_desc'];
        });

        //下架检测
//        $house_info['off_type'] = $this->is_off($source_url,$html);

        //装修情况
        $houseInfo['house_fitment'] = '';

        //发布人电话
        $houseInfo['owner_phone'] = "";

        //房源图片
        array_pop( $houseInfo['house_pic_unit']);
        // $houseInfo['house_pic_layout']=$houseInfo['house_pic_unit'][5];
        $houseInfo['house_pic_unit'] = implode('|', $houseInfo['house_pic_unit']);

        //来源
        $houseInfo['source'] = '14';
        //创建时间
        $houseInfo['created'] = time();
        //更新时间
        $houseInfo['updated'] = time();
        //种子url
        $houseInfo['source_url'] = $source_url;

        $houseInfo['tag'] = $this->getTags($html);
        return $houseInfo;
    }

    public function getTags($html){

        $tags = [];
        \QL\QueryList::Query($html,[
            'tag' => ['.tag-div > span', 'text']
        ])->getData(function($item)use(&$tags) {
            $item['tag'] && $tags[] = $item['tag'];
            return $item;
        });

        return implode("#",$tags);
    }

    /* 获取最新的房源种子
    * @author robert
    * @return type
    */
    public function callNewData(){
        return $this->house_page();
    }
	/*
	 * 获取各类搜索条件
	 */
	Public function get_condition($PRE_URL){
        $condition = \QL\QueryList::run("Request",[
            "target"=>$PRE_URL,
        ])->setQuery([
            'dis'=>['div.tab-body-item.show > span > a','href','',function($item){
                preg_match('/(s\d+)/',$item,$dis);
                return $dis[1];
            }],
            'room'=>['div.single-line > span > a','href','',function($item){
                preg_match('/(r\d+)/',$item,$room);
                return $room[1];
            }],
            'price'=>['div.single-line:nth-child(3) > span > a:nth-child(1)','href','',function($item){
                preg_match('/(co\d+-\d+)/',$item,$price);
                return $price[1];
            }]
        ])->getData(function($item){
            return $item;
        });

        $data = [];
        foreach($condition as $key=>$val){
            if(!empty($val['dis'])){
                $data['dis'][] = $val['dis'];
            }
            if(!empty($val['room'])){
                $data['room'][] = $val['room'];
            }
            if(!empty($val['price'])){
                $data['price'][] = $val['price'];
            }
        }
	    foreach($data['dis'] as $DIS){
	        foreach($data['price'] as $PRICE){
	            foreach($data['room'] as $ROOM){
	                $url_list[] = $PRE_URL."/list/".$DIS.'_'.$PRICE.'_'.$ROOM;
	            }
	        }
	    }
        return $url_list;
	}
	
	/*
	 * 获取搜索条件下的最大页
	 */
	Public function get_maxPage($url){
        return $maxPage = \QL\QueryList::run("Request",[
            "target"=>$url,
        ])->setQuery([
            'count'=>['h4.title > span:nth-child(1)','text','',function($item){
                return ceil($item/20);
            }],
        ])->getData(function($item){
            return $item['count'];
        });
	}
}