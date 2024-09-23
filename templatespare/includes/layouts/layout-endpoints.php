<?php

if(!class_exists('AFTMLS_RestApi_Request')){

    class AFTMLS_RestApi_Reques_Controller{
        private $namespace;
        private $query_base;

        public function __construct() {
            $this->namespace = 'templatespare/v1';
            $this->query_base = 'demo-lists';
        }

        public function templatespare_register_routes() {
            

            register_rest_route(
                'templatespare/v1','single-demo-content',
                array(
                    array(
                        'methods' => \WP_REST_Server::READABLE,
                        'callback'            => array( $this, 'templatespare_get_single_demo_list_items' ),
                        'permission_callback' => function () {
                        return true;
                    },
                    ),
                )
            );
            
        }

       
        public function templatespare_get_single_demo_list_items(\WP_REST_Request $request){
            $params = $request->get_params();
            $data['singleDemo']=$this->templatespare_ajax_render_demo_lists($params['cat'],$params['selectedtheme']);
            $data['tags']=$this->templatespare_ajax_render_demo_tags_lists($params['selectedtheme']);
            
            return  $data;

        }

        function templatespare_ajax_render_demo_lists($slug,$theme){
            
           
            $all_demos = templatespare_templates_demo_list($theme);
            
            
            $themecheck = explode('-',$theme);
            $parentNode =  array();
            $final_array = array();
           
            foreach( $all_demos as $value){
                foreach($value['demodata'] as $filtered_data){
                    $empty_array = array(
                                'data'=>$value['data'],
                                    'free'=>$value['free'],
                                   'premium'=>$value['premium'],
                                   'slug'=>$filtered_data['slug'],
                                   'theme'=>$filtered_data['theme'],
                                   'name'=>$filtered_data['name'],
                                   'preview'=>$filtered_data['preview'],
                                   'tags'=>$filtered_data['tags'],
                                   'parent'=>'',
                                   'plugins'=>isset($filtered_data['plugins'])?$filtered_data['plugins']:"",
                                   "theme_type"=>($theme==$filtered_data['slug'] && in_array('child',$filtered_data['tags']))?'true':$value['free'],
                                   'installed_themes'=>$this->templatespare_installed_themes()
            
                               );

                               array_push($parentNode,$empty_array);
                
                }
            
            }
           
            

            

            return $parentNode;
            
        }

      

        public function templatespare_installed_themes(){
            $installed_themes = [];
            foreach ( (array) wp_get_themes() as $theme_dir => $themes ){
                $installed_themes[] =$themes->name;
            }
        
            return $installed_themes;
        }
        public function templatespare_ajax_render_demo_tags_lists($theme){
        
           
            $tagsdata = templatespare_get_tags_filtered_data($theme);
            return json_encode($tagsdata);
        
        }

        

         public function templatespare_get_theme_count($parent){

            $all_demos = templatespare_templates_demo_list();
            $numberoftheme=count(array_values($all_demos[$parent]['demodata']));
           
            return $numberoftheme;

         }
    }


    

}