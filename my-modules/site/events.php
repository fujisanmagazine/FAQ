<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * site Events File
 *
 */
class site_events
{
	/**
	* Class constructor
	*
	* The constructor takes the $core_events as the param.
	* Inside this you will register your events to interact
	* with the core system. 
	*/
	function __construct(&$core_events)
	{
	 // for add site_info on article page, category page
     $core_events->register('viewtop', $this, 'viewtop');
     $core_events->register('viewarticle', $this, 'viewarticle');
     $core_events->register('viewcategory', $this, 'viewcategory');
     $core_events->register('searchresult', $this, 'searchresult');
     $core_events->register('viewallarticles', $this, 'viewallarticles');
     $core_events->register('content_all_categories_with_site_id', $this, 'content_all_categories_with_site_id');
     $core_events->register('thirdcatetory', $this, 'content_thirdcatetory');
     $core_events->register('secondcatetory', $this, 'content_secondcatetory');
     $core_events->register('categorydata', $this, 'categorydata');
     $core_events->register('thankyou', $this, 'thankyou');
     $core_events->register('addrating', $this, 'insert_rating_log');
	}

    function thankyou()
    {

	    $CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);
		
		$data['settings']=$CI->init_model->settings;

		$template = 'thanks';

		$dir='front';
		// are we caching?
		if ($CI->init_model->get_setting('cache_time') > 0)
		{
			$CI->output->cache($CI->init_model->get_setting('cache_time'));
		}
		
		$data = $this->set_siteinfo_with_site_id($data,$useragent);

		// Check the body exists
		$data['body'] = $this->load_base_body($template, $dir, $data, $useragent,$is_shiftjis);
		
        // Now check the layout exists
		$this->load_layout($dir, $data, $useragent,$is_shiftjis);
		// finally show the last hook
		$CI->core_events->trigger('display_template');	

		return "done";
    }

    function viewtop()
    {

	    $CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);

		$data['parents'] = $this->get_categories_by_parent_based_useragent(0);
		$data = $this->get_categories_based_useragent($data);
		//For topSearch
		$data['cat_tree'] = $this->get_cats_for_select_with_site_id();		
		//$data['latest'] = $CI->article_model->get_latest(10);
		//$data['pop'] = $CI->article_model->get_most_popular(10);
		$data['pop'] = $this->get_most_popular_with_site_id(10);
		$data['title'] = $CI->init_model->get_setting('site_name');
		
		$data['settings']=$CI->init_model->settings;

		$template = 'home';

		$dir='front';
		// are we caching?
		if ($CI->init_model->get_setting('cache_time') > 0)
		{
			$CI->output->cache($CI->init_model->get_setting('cache_time'));
		}
		
		$data = $this->set_siteinfo_with_site_id($data,$useragent);

		// Check the body exists
		$data['body'] = $this->load_base_body($template, $dir, $data, $useragent,$is_shiftjis);
		
        // Now check the layout exists
		$this->load_layout($dir, $data, $useragent,$is_shiftjis);
		// finally show the last hook
		$CI->core_events->trigger('display_template');	

		return "done";
    }

    function viewarticle($uri='')
    {
	    $CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);
		$CI->load->library('user_agent');

		$data['title'] = $CI->init_model->get_setting('site_name');
		if($uri<>'' && $uri<>'index') 
		{
			$uri = $CI->input->xss_clean($uri);
			$article = $CI->article_model->get_article_by_uri($uri);
			if($article)
			{
				$data['article'] = $article;
				$CI->article_model->add_hit($data['article']->article_id);
				
				//function for insert aritcle_id, referre, date  
				$param = array(
				'article_id' => (int) $data['article']->article_id, 
				'visit_datetime' => date('Y-m-d H:i:s',time()), 
				'referrer' => $CI->agent->referrer()
				);
				$this->insert_clickinfo($param);
				//format description
				$data['article']->article_description = $CI->article_model->glossary($data['article']->article_description);
				
				// call hooks
				$arr = array('article_id' => $data['article']->article_id, 'article_title' => $data['article']->article_title);
				if($CI->core_events->trigger('article/title', $arr) != '')
				{
					$data['article']->article_description = $CI->core_events->trigger('article/title', $arr);
				}
				$arr = array('article_id' => $data['article']->article_id, 'article_description' => $data['article']->article_description);
				if($CI->core_events->trigger('article/description', $arr) != '')
				{
					$data['article']->article_description = $CI->core_events->trigger('article/description', $arr);
				}
				
				$data['article_cats'] = $CI->category_model->get_cats_by_article($data['article']->article_id);
				$data['attach'] = $CI->article_model->get_attachments($data['article']->article_id);
				$data['author'] = $CI->users_model->get_user_by_id($data['article']->article_author);
				
				$data['title'] = $data['article']->article_title. ' | '. $CI->init_model->get_setting('site_name');
				$data['meta_keywords'] = $data['article']->article_keywords;
				$data['meta_description'] = $data['article']->article_short_desc;
				$data['comments'] = $CI->comments_model->get_article_comments($data['article']->article_id);
				$data['comments_total'] = $CI->comments_model->get_article_comments_count($data['article']->article_id);
				
				$data['comment_author'] = get_cookie('kb_author', TRUE);
				$data['comment_author_email'] = get_cookie('kb_email', TRUE);
				
				$data['comment_template'] = $CI->init_model->load_body('comments', 'front', $data);
			}
			else
			{
				$data = '';
			}
		}
		else
		{
			$data='';
		}

		//For topSearch
		$data['cat_tree'] = $this->get_cats_for_select_with_site_id();		

		$data['settings']=$CI->init_model->settings;

		$template = 'article';

		$dir='front';
		// are we caching?
		if ($CI->init_model->get_setting('cache_time') > 0)
		{
			$CI->output->cache($CI->init_model->get_setting('cache_time'));
		}
		
		$data = $this->set_siteinfo_with_site_id($data,$useragent);

		// Check the body exists
		$data['body'] = $this->load_base_body($template, $dir, $data, $useragent,$is_shiftjis);
		
        // Now check the layout exists
		$this->load_layout($dir, $data, $useragent,$is_shiftjis);
		// finally show the last hook
		$CI->core_events->trigger('display_template');	

		return "done";
    }


    function viewcategory($uri='')
    {
	    $CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);

		if($uri<>'' && $uri<>'index') 
		{
			//echo $uri;
			$uri = $CI->input->xss_clean($uri);
			$data['cat']=$this->get_cat_by_uri_with_useragent($uri,$useragent);
			if($data['cat'])
			{
				$id = $data['cat']->cat_id;
				$data['title'] = $data['cat']->cat_name. ' | '. $CI->init_model->get_setting('site_name');
				$data['parents'] = $this->get_categories_by_parent_based_useragent($id);
				//pagination
				$CI->load->library('pagination');

				$config['total_rows'] = $this->get_articles_by_catid_with_useragent($id, 0, 0, TRUE,$useragent);
				$config['per_page'] = $CI->init_model->get_setting('max_search');

				$config['uri_segment'] = '3';
				$config['base_url'] = site_url("category/". $uri);

				$CI->pagination->initialize($config); 
				$data["pagination"] = $CI->pagination->create_links();
				
				$data['articles'] = $CI->article_model->get_articles_by_catid($id, $config['per_page'], $CI->uri->segment(3), FALSE);
			}
			else
			{
				//表示するカチE��リーがなかったら全てのカチE��リーを表示するペ�EジへぁE��る、E
				//redirect('all');
				redirect('/kb');
				
			}
		}
		else 
		{
			$data['title'] = $CI->init_model->get_setting('site_name');
			$data['parents'] = $CI->category_model->get_categories_by_parent(0);
		}

		//For topSearch
		$data['cat_tree'] = $this->get_cats_for_select_with_site_id();		

		$data['settings']=$CI->init_model->settings;

		$template = 'category';

		$dir='front';
		// are we caching?
		if ($CI->init_model->get_setting('cache_time') > 0)
		{
			$CI->output->cache($CI->init_model->get_setting('cache_time'));
		}
		
		$data = $this->set_siteinfo_with_site_id($data,$useragent);

		// Check the body exists
		$data['body'] = $this->load_base_body($template, $dir, $data, $useragent,$is_shiftjis);
		
        // Now check the layout exists
		$this->load_layout($dir, $data, $useragent,$is_shiftjis);
		// finally show the last hook
		$CI->core_events->trigger('display_template');	

		return "done";
	}
    function searchresult()
    {
		
		$CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);
		if($is_shiftjis){
		$input = $this->convertShiftjisToUtf($CI->input->post('searchtext', TRUE));
		}else{
		$input = $CI->input->post('searchtext', TRUE);
		}
		
		$category = (int)$CI->input->post('category', TRUE);
		
		if($input <> '' || $category <> '')
		{
			if ($input)
			{
				$insert = array('searchlog_term' => $input);
				$CI->db->insert('searchlog', $insert);
			}
			
			
			$CI->db->from('articles');
			$CI->db->join('article2cat', 'articles.article_id = article2cat.article_id', 'left');
			if($category)
			{
				$CI->db->where('category_id', $category);
			}
			$CI->db->where('article_display', 'Y');
			
			// This is a hack found here:
			// http://codeigniter.com/forums/viewthread/122223/
			// And here:
			// http://68kb.com/support/topic/better-keyword-handling-in-search-a-solution
			if($input)
		    {
		    	$keywords = array();
		    	$keywords = explode(" ", $input);
		    	$numkeywords = count($keywords);
		    	$wherestring = "";
		    	for ($i = 0; $i < $numkeywords; $i++)
		    	{
					if ($i > 0)
					{
						$wherestring .= " AND ";
					}
					$wherestring = $wherestring .
		    			" (article_title LIKE '%". mysql_real_escape_string($keywords[$i]) .
		    			"%' OR article_short_desc LIKE '%" . mysql_real_escape_string($keywords[$i]) .
		    			"%' OR article_description LIKE '%". mysql_real_escape_string($keywords[$i]) ."%') ";
		    	}
		    	$CI->db->where($wherestring,NULL,FALSE);
		    }
			
			$CI->db->orderby('article_order', 'DESC');
			$CI->db->orderby('article_hits', 'DESC');
			
			$data['articles'] = $CI->db->get();
			
			$data['searchtext'] = $input;
			$data['category'] = $category;
		}
		

		//For topSearch
		//$data['cat_tree'] = $CI->category_model->get_cats_for_select();		
		$data['cat_tree'] = $this->get_cats_for_select_with_site_id();		

		$data['settings']=$CI->init_model->settings;

		$template = 'search';

		$dir='front';
		// are we caching?
		if ($CI->init_model->get_setting('cache_time') > 0)
		{
			$CI->output->cache($CI->init_model->get_setting('cache_time'));
		}
		
		$data = $this->set_siteinfo_with_site_id($data,$useragent);

		// Check the body exists
		$data['body'] = $this->load_base_body($template, $dir, $data, $useragent,$is_shiftjis);
		
        // Now check the layout exists
		$this->load_layout($dir, $data, $useragent,$is_shiftjis);
		// finally show the last hook
		$CI->core_events->trigger('display_template');	
		
		
		//$this->init_model->display_template('search', $data);
		return "done";
	}


    function viewallarticles(){

	    $CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);
		
		$data['parents'] = $this->get_categories_by_parent_based_useragent(0);
		foreach($data['parents']->result() as $row)
		{
			$data['articles'][$row->cat_id] =$CI->article_model->get_articles_by_catid($row->cat_id);
		}
		$data['title'] = $CI->init_model->get_setting('site_name');
		$this->display_template_based_useragent('all',$data,$useragent,$is_shiftjis,'front');
		return "done";
	}


    function categorydata($cat_id)
    {
		$id = $cat_id;
	    $CI =& get_instance();
		$useragent = $CI->input->user_agent();

		$CI->db->from('categories');
		$CI->db->join('categories2site', 'categories.cat_id = categories2site.category_id', 'left');
		$CI->db->orderby('cat_order', 'DESC')->orderby('cat_name', 'asc')->where('cat_parent', $id)->where('cat_display', 'Y');
		$CI->db->where('site_id', 2);
		$query = $CI->db->get();
		echo "1";
		$data['category_data'] = $query;
		return $data; 
	}


    function content_secondcatetory($cat_id)
    {
		$data['cat_id'] = $cat_id;
	    $CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);
		$data = $this->set_siteinfo_with_site_id($data,$useragent);

		$id = (int) $cat_id;	
		$CI->db->from('categories');
		$CI->db->join('categories2site', 'categories.cat_id = categories2site.category_id', 'left');
		$CI->db->orderby('cat_order', 'DESC')->orderby('cat_name', 'asc')->where('cat_parent', $id)->where('cat_display', 'Y');
		$useragent = $CI->input->user_agent();
		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}
		$query = $CI->db->get();
		$data['query'] = $query;
		$data = $this->set_siteinfo_with_site_id($data,$useragent);
        if ($is_shiftjis){
            return $this->convertUtfToShiftjis($CI->load->view('front/'.$data['template_location'].'/secondcategory.php', $data));
		}else{
			return $CI->load->view('front/'.$data['template_location'].'/secondcategory.php', $data);
		}
	}


    function content_thirdcatetory($cat_id)
    {
		$data['cat_id'] = $cat_id;
	    $CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);

		$id = (int) $cat_id;	
		$CI->db->from('categories');
		$CI->db->join('categories2site', 'categories.cat_id = categories2site.category_id', 'left');
		$CI->db->orderby('cat_order', 'DESC')->orderby('cat_name', 'asc')->where('cat_parent', $id)->where('cat_display', 'Y');
		$useragent = $CI->input->user_agent();
		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}
		$query = $CI->db->get();
		$data['query'] = $query;
		$data = $this->set_siteinfo_with_site_id($data,$useragent);
        if ($is_shiftjis){
            return $this->convertUtfToShiftjis($CI->load->view('front/'.$data['template_location'].'/thirdcategory.php', $data));
		}else{
			return $CI->load->view('front/'.$data['template_location'].'/thirdcategory.php', $data);
		}
	}


	function display_template_based_useragent($template,$data,$useragent,$is_shiftjis, $dir='front')
	{
        $CI =& get_instance();

		$data['settings']=$CI->init_model->settings;
		
		// check directory
		if ($dir=='admin')
		{
			define('IN_ADMIN', TRUE);
		}
		else
		{
			$dir='front';
			// are we caching?
			if ($CI->init_model->get_setting('cache_time') > 0)
			{
				$CI->output->cache($CI->init_model->get_setting('cache_time'));
			}
		}
		$data = $this->set_siteinfo_with_site_id($data,$useragent);

		// Check the body exists
		$data['body'] = $this->load_body($template, $dir, $data, $useragent,$is_shiftjis);
		
        // Now check the layout exists
		$this->load_layout($dir, $data, $useragent,$is_shiftjis);
		// finally show the last hook
		$CI->core_events->trigger('display_template');	
	}

	/**
	 * Load Body Template
	 *
	 */
	function load_base_body($template, $dir='front', $data, $useragent, $is_shiftjis)
	{
	    $CI =& get_instance();
		$data['settings']=$CI->init_model->settings;

		$body_file =$this ->get_body_file_with_site_id($template, $dir='front', $data, $useragent, $is_shiftjis);

		$data['topsearch'] = $this->get_template_data_with_site_id($dir='front','topsearch', $data, $useragent, false);	
		//file chekc if not exist load default page
		if ($CI->init_model->test_exists($body_file))
		{			
        	if($is_shiftjis){
            	return $this->convertUtfToShiftjis($CI->load->view($body_file, $data, true));
			}else{
				return $CI->load->view($body_file, $data, true);
			}
		}
		else
		{
        	if ($is_shiftjis){
            	return $this->convertUtfToShiftjis($CI->load->view($dir.'/default/'.$template, $data, true));
			}else{
				return $CI->load->view($dir.'/default/'.$template, $data, true);
			}
		}
	}



	/**
	 * Load Body Template
	 *
	 */
	function load_body($template, $dir='front', $data, $useragent, $is_shiftjis)
	{
	    $CI =& get_instance();
		$data['settings']=$CI->init_model->settings;

		$body_file =$this ->get_body_file_with_site_id($template, $dir='front', $data, $useragent, $is_shiftjis);
		$data['topsearch'] = $this->get_template_data_with_site_id($dir='front','topsearch', $data, $useragent, $is_shiftjis);	
		//file chekc if not exist load default page
		if ($CI->init_model->test_exists($body_file))
		{			
        	if($is_shiftjis){
            	return $this->convertUtfToShiftjis($CI->load->view($body_file, $data, true));
			}else{
				return $CI->load->view($body_file, $data, true);
			}
		}
		else
		{
        	if ($is_shiftjis){
            	return $this->convertUtfToShiftjis($CI->load->view($dir.'/default/'.$template, $data, true));
			}else{
				return $CI->load->view($dir.'/default/'.$template, $data, true);
			}
		}
	}

	
	// ------------------------------------------------------------------------
	
	/**
	 * Load layout Template
	 *
	 */
	function load_layout($dir='front', $data, $useragent, $is_shiftjis)
	{
	    $CI =& get_instance();
		$data['settings']=$CI->init_model->settings;
		$data['settings']['site_name']=$data['title'];
		
		$layout_file=$this -> get_layout_template_with_site_id($dir='front', $data, $useragent, $is_shiftjis);
		if ($CI->init_model->test_exists($layout_file))
		{
        	if ($is_shiftjis){
				//set charset SHIFT-JIS
				$data = $this->setlayoutsetting($data,$is_shiftjis);
				//set header info
				$data['header'] = $this->get_template_data_with_site_id($dir='front','header', $data, $useragent, $is_shiftjis);	
				//$data['topsearch'] = $this->get_template_data_with_site_id($dir='front','topsearch', $data, $useragent, $is_shiftjis);	
				$data['footer'] = $this->get_template_data_with_site_id($dir='front','footer', $data, $useragent, $is_shiftjis);	
				return $this->convertUtfToShiftjis($CI->load->view($layout_file, $data));			
			}else{
				//set charset SHIFT-JIS
				$data = $this->setlayoutsetting($data,$is_shiftjis);
				$data['header'] = $this->get_template_data_with_site_id($dir='front','header', $data, $useragent, $is_shiftjis);	
				//$data['topsearch'] = $this->get_template_data_with_site_id($dir='front','topsearch', $data, $useragent, $is_shiftjis);	
				$data['footer'] = $this->get_template_data_with_site_id($dir='front','footer', $data, $useragent, $is_shiftjis);	
				return $CI->load->view($layout_file, $data);
			}
		}
		else
		{
        	if ($is_shiftjis){
				//set charset SHIFT-JIS
				$data = $this->setlayoutsetting($data,$is_shiftjis);
				$data['header'] = $this->get_template_data_with_site_id($dir='front','header', $data, $useragent, $is_shiftjis);	
				//$data['topsearch'] = $this->get_template_data_with_site_id($dir='front', 'topsearch',$data, $useragent, $is_shiftjis);	
				$data['footer'] = $this->get_template_data_with_site_id($dir='front', 'footer',$data, $useragent, $is_shiftjis);	
				return $this->convertUtfToShiftjis($CI->load->view($dir.'/default/layout.php',$data));			
			}else{
				//set charset SHIFT-JIS
				$data = $this->setlayoutsetting($data,$is_shiftjis);
				$data['header'] = $this->get_template_data_with_site_id($dir='front','header', $data, $useragent, $is_shiftjis);	
				//$data['topsearch'] = $this->get_template_data_with_site_id($dir='front','topsearch', $data, $useragent, $is_shiftjis);	
				$data['footer'] = $this->get_template_data_with_site_id($dir='front','footer', $data, $useragent, $is_shiftjis);	
				return $CI->load->view($dir.'/default/layout.php',$data);			
			}
		}
	}

	function select_template_by_useragent($data)
	{
        $CI =& get_instance();
		$useragent = $CI->input->user_agent();
		$is_shiftjis = $this->is_shiftjis($useragent);
		$this -> display_template($data,$useragent,$is_shiftjis);
	}
	
	
	function is_mobile($useragent)
	{
        if ($this -> isDoCoMo($useragent)) {
            return true;
        } elseif ($this -> isEZweb($useragent)) {
            return true;
        } elseif ($this ->isSoftBank($useragent)) {
            return true;
        } elseif ($this ->isWillcom($useragent)) {
            return true;
        }
	        return false;
    }

     function is_shiftjis($useragent)
	{
        if ($this -> isDoCoMo($useragent)) {
            return true;
        } elseif ($this -> isEZweb($useragent)) {
            return false;
        } elseif ($this ->isSoftBank($useragent)) {
            return true;
        } elseif ($this ->isWillcom($useragent)) {
            return true;
        }
	 return false;
    }

    function isDoCoMo($useragent = null)
    {
        if (is_null($useragent)) {
	        $CI =& get_instance();
			$$useragent = $CI->input->user_agent();
        }

        if (preg_match('!^DoCoMo!', $useragent)) {
            return true;
        }

        return false;
    }

    function isEZweb($useragent = null)
    {
        if (is_null($useragent)) {
	        $CI =& get_instance();
			$$useragent = $CI->input->user_agent();
        }

        if (preg_match('!^KDDI-!', $useragent)) {
            return true;
        } elseif (preg_match('!^UP\.Browser!', $useragent)) {
            return true;
        }

        return false;
    }

    function isSoftBank($useragent = null)
    {
        if (is_null($useragent)) {
	        $CI =& get_instance();
			$useragent = $CI->input->user_agent();
        }

        if (preg_match('!^SoftBank!', $useragent)) {
            return true;
        } elseif (preg_match('!^Semulator!', $useragent)) {
            return true;
        } elseif (preg_match('!^Vodafone!', $useragent)) {
            return true;
        } elseif (preg_match('!^Vemulator!', $useragent)) {
            return true;
        } elseif (preg_match('!^MOT-!', $useragent)) {
            return true;
        } elseif (preg_match('!^MOTEMULATOR!', $useragent)) {
            return true;
        } elseif (preg_match('!^J-PHONE!', $useragent)) {
            return true;
        } elseif (preg_match('!^J-EMULATOR!', $useragent)) {
            return true;
        }

        return false;
    }

    function isWillcom($useragent = null)
    {
        if (is_null($useragent)) {
	        $CI =& get_instance();
			$useragent = $CI->input->user_agent();
        }

        if (preg_match('!^Mozilla/3\.0\((?:DDIPOCKET|WILLCOM);!', $useragent)) {
            return true;
        }

        return false;
    }
	
    /**
     * Checks whether or not the user agent is Willcom by a given user agent string.
     */
    function convertUtfToShiftjis($data)
    {
		return mb_convert_encoding($data, "SJIS-win", "UTF-8");
    }

    function convertShiftjisToUtf($data)
    {
		return mb_convert_encoding($data, "UTF-8", "SJIS-win");
    }

	function get_categories_based_useragent($data)
	{
	       $arr = array();
	       $CI =& get_instance();
	       $CI->db->distinct();
	       $CI->db->from('categories');
	       $CI->db->join('categories2site', 'categories.cat_id = categories2site.category_id', 'left');
	       $CI->db->orderby('cat_order', 'DESC')->orderby('cat_name', 'asc')->where('cat_display', 'Y');
		$useragent = $CI->input->user_agent();
		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}
		$query = $CI->db->get();
		$data['categories']= $query;
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$num = 0;
				$query = $this->get_categories_by_parent_based_useragent($row->cat_id);
				if($query !== null){
					$data['childcateories'.$num] = $query;
				}
				$num = $num + 1;
			}
		}		
		return $data;
	}
	
	function content_all_categories_with_site_id(){

	    $CI =& get_instance();
	    $CI->db->distinct();
	    $CI->db->from('categories');
	    $CI->db->join('categories2site', 'categories.cat_id = categories2site.category_id', 'left');
	    $CI->db->orderby('cat_order', 'DESC')->orderby('cat_name', 'asc')->where('cat_parent', 0)->where('cat_display', 'Y');
		$useragent = $CI->input->user_agent();
		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}
		$query = $CI->db->get();
		if($query !== null){
			$output = '';
			foreach ($query->result() as $row)
			{
				$id = (int) $row->cat_id;
				$output.='<h2>'.$row->cat_name.'</h2>';
				$output .= '<P>'.$row->cat_description.'</P>';
				$query2 = $this->get_categories_by_parent_based_useragent($id);
				
				//article
				$articlequery = $this->get_articles_by_catid_with_useragent($id, $limit=0, $current_row = 0, $show_count=FALSE,$useragent='');
				if($articlequery !== null)
				{
					foreach ($articlequery->result() as $row2)
					{
						$output.='<br/>'.$row2->article_title.'<br/>';
						$output .= '<br/>'.$row2->article_description.'<br/>';
					}
				}
				
				//category
				if($query2 !== null){
					foreach ($query2->result() as $row2)
					{
						$output.='<br/>'.$row2->cat_name.'<br/>';
						$output .= '<br/>'.$row2->cat_description.'<br/>';
						$id2 = (int) $row2->cat_id;
					
						//article
						$articlequery = $this->get_articles_by_catid_with_useragent($id2, $limit=0, $current_row = 0, $show_count=FALSE,$useragent='');
						if($articlequery !== null)
						{
							foreach ($articlequery->result() as $row3)
							{
								$output.='<br/>'.$row3->article_title.'<br/>';
								$output .= '<br/>'.$row3->article_description.'<br/>';
							}
						}
					}
				}
		
			}	

		}
		echo $output;
	}

	/**
	 * Get Categories By Parent Based site.
	 */
	function get_categories_by_parent_based_useragent($parent)
	{
	    $CI =& get_instance();
	    $CI->db->distinct();
	    $CI->db->from('categories');
	    $CI->db->join('categories2site', 'categories.cat_id = categories2site.category_id', 'left');
	    $CI->db->orderby('cat_order', 'DESC')->orderby('cat_name', 'asc')->where('cat_parent', $parent)->where('cat_display', 'Y');
		$useragent = $CI->input->user_agent();

		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}

		$query = $CI->db->get();
		return $query;
	}

	function get_cat_by_uri_with_useragent($uri,$useragent=''){
	    $CI =& get_instance();
		$CI->db->from('categories');
	    $CI->db->join('categories2site', 'categories.cat_id = categories2site.category_id', 'left');
		if($useragent == '')
		{
			$useragent = $CI->input->user_agent();
		}
		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}
		$CI->db->where('cat_uri', $uri)->where('cat_display', 'Y');
		$query = $CI->db->get();
		$data = $query->row();
		$query->free_result();
		return  $data;
	}
	
	function get_articles_by_catid_with_useragent($id, $limit=0, $current_row = 0, $show_count=FALSE,$useragent='')
	{
	    $CI =& get_instance();
		$id = (int)$id;
		$CI->db->from('articles');
		$CI->db->join('article2cat', 'articles.article_id = article2cat.article_id', 'left');
		$CI->db->join('articles2site', 'articles.article_id = articles2site.article_id', 'left');
		$CI->db->where('category_id', $id);
		$CI->db->where('article_display', 'Y');
		if($useragent == '')
		{
			$useragent = $CI->input->user_agent();
		}
		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}
		if ($show_count)
		{
			return $CI->db->count_all_results();
		}
		if ($limit > 0)
		{
			$CI->db->limit($limit, $current_row);
		}
		$query = $CI->db->get();
		return $query;
	}

	
	function get_body_file_with_site_id($template, $dir='front', $data, $useragent, $is_shiftjis){
		
		if($dir=='front'){
			$CI =& get_instance();
			//PC
			if($this->is_mobile($useragent) == false){
				if ($data['template_location'] !== null && $data['template_location'] !== ''){
					$body_file = $dir.'/'.$data['template_location'].'/'.$template.'.php';
					return $body_file;
				}else{
					$body_file = $dir.'/'.$data['settings']['template'].'/'.$template.'.php';
					return $body_file;
				}
			//Mobile
			}else{
				if ($data['template_location'] !== null && $data['template_location'] !== ''){
					$body_file = $dir.'/'.$data['template_location'].'/'.$template.'.php';
					return $body_file;
				}else{
					$body_file = $dir.'/'.$data['settings']['template'].'/'.$template.'.php';
					return $body_file;
				}
			}
		}
		if ($dir=='admin'){
			$body_file = $dir.'/'.$data['settings']['admin_template'].'/'.$template.'.php';
			return $body_file;
		}
	}

		
	function get_layout_template_with_site_id($dir='front', $data, $useragent, $is_shiftjis){
		if (defined('IN_ADMIN')){
			$layout_file = $dir.'/'.$data['settings']['admin_template'].'/layout.php';
		}else{
			$CI =& get_instance();
			//PC
			if($this->is_mobile($useragent) == false){
				if ($data['template_location'] !== null && $data['template_location'] !== ''){
					$layout_file = $dir.'/'.$data['template_location'].'/layout.php';
					return $layout_file;
				}else{
					$layout_file = $dir.'/'.$data['settings']['template'].'/layout.php';
					return $layout_file;
				}
			//Mobile
			}else{

				if ($data['template_location'] !== null && $data['template_location'] !== ''){
					$layout_file = $dir.'/'.$data['template_location'].'/layout.php';
					return $layout_file;
				}else{
					$layout_file = $dir.'/'.$data['settings']['template'].'/layout.php';
					return $layout_file;
				}
			}
		}
	}	

	function get_template_data($template,$data,$is_shiftjis){
			$CI =& get_instance();
	        if ($is_shiftjis){
				//set header info
				return $this->convertUtfToShiftjis($CI->load->view($template,$data,true));			
			}else{
				return $CI->load->view($template,$data,true);
			}
	}

	function get_template_data_with_site_id($dir='front',$content, $data, $useragent, $is_shiftjis){
		if($dir=='front'){
			$CI =& get_instance();
			//PC
			if($this->is_mobile($useragent) == false){
				if ($data['template_location'] !== null && $data['template_location'] !== ''){
					$template_file = $dir.'/'.$data['template_location'].'/'.$content.'.php';
					return $this->get_template_data($template_file,$data,$is_shiftjis);
				}else{
					$template_file = $dir.'/'.$data['settings']['template'].'/'.$content.'.php';
					return $this->get_template_data($template_file,$data,$is_shiftjis);
				}

			}else{
				if ($data['template_location'] !== null && $data['template_location'] !== ''){
					$template_file = $dir.'/'.$data['template_location'].'/'.$content.'.php';
					return $this->get_template_data($template_file,$data,$is_shiftjis);
				}else{
					$template_file = $dir.'/'.$data['settings']['template'].'/'.$content.'.php';
					return $this->get_template_data($template_file,$data,$is_shiftjis);
				}
			}
		}
		if ($dir=='admin'){
			$template_file = $dir.'/'.$data['settings']['admin_template'].'/'.$content.'.php';
			return $this->get_template_data($template_file,$data,$is_shiftjis);
		}
	}



	function set_siteinfo_with_site_id($data,$useragent){
		// meta content
		//PC
		$CI =& get_instance();
		if($this->is_mobile($useragent) == false){
				$CI->db->select('template,title,keywords,description');
				$CI->db->from('siteinfo');
				$CI->db->where('site_id', 1);
				$query = $CI->db->get();
				if ($query->num_rows() > 0){
					foreach ($query->result() as $row){
					$data['title'] = $row->title;
					$data['meta_keywords'] = $row->keywords;
					$data['meta_description'] = $row->description;
					$data['template_location'] = $row->template;
					}
				}else{
					if ( ! isset($data['title']))
					{
						$data['title'] = $CI->init_model->get_setting('site_name');
					}	
					if ( ! isset($data['meta_keywords']))
					{
						$data['meta_keywords'] = $CI->init_model->get_setting('site_keywords');
					}
					if ( ! isset($data['meta_description']))
					{
						$data['meta_description'] = $CI->init_model->get_setting('site_description');
					}
				}
		//Mobile
		}else{
				$CI->db->select('template,title,keywords,description');
				$CI->db->from('siteinfo');
				$CI->db->where('site_id', 2);
				$query = $CI->db->get();
				if ($query->num_rows() > 0){
					foreach ($query->result() as $row){
					$data['title'] = $row->title;
					$data['meta_keywords'] = $row->keywords;
					$data['meta_description'] = $row->description;
					$data['template_location'] = $row->template;
					}
				}else{
					if ( ! isset($data['title']))
					{
						$data['title'] = $CI->init_model->get_setting('site_name');
					}	
					if ( ! isset($data['meta_keywords']))
					{
						$data['meta_keywords'] = $CI->init_model->get_setting('site_keywords');
					}
					if ( ! isset($data['meta_description']))
					{
						$data['meta_description'] = $CI->init_model->get_setting('site_description');
					}
				}
		}
		return $data;
	}

	function setlayoutsetting($data,$is_shiftjis){
		$data['charset'] = $this->getsettinginfo("charset",$is_shiftjis);
		$data['doctype'] = $this->getsettinginfo("doctype",$is_shiftjis);
		$data['xmlns'] = $this->getsettinginfo("xmls",$is_shiftjis);
		$data['header'] = $this->getsettinginfo("header",$is_shiftjis);
		return $data;
	}

    function getsettinginfo($type,$is_shiftjis){
		if($type =="doctype"){
			return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		}
		if($type =="xmlns"){
				return '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">';
		}
		if($type =="charset"){
			if($is_shiftjis){
				return 'charset="Shift_JIS"';
			}else{
				return 'charset="UTF-8"';
			}
		}
		if($type =="header"){
			$CI =& get_instance();

			if($is_shiftjis){
				return 'charset="Shift_JIS"';
			}else{
				return 'charset="UTF-8"';
			}
		}
	}

	function get_cats_for_select_with_site_id($prefix='', $parent=0, $article_id='', $admin=FALSE)
	{
		$CI =& get_instance();
		$arr = array();
		$CI->db->select('cat_id,cat_uri,cat_name,cat_description')->from('categories'); 
	    $CI->db->join('categories2site', 'categories.cat_id = categories2site.category_id', 'left');
		$CI->db->orderby('cat_order', 'DESC')->orderby('cat_name', 'asc')->where('cat_parent', $parent);
		$useragent = $CI->input->user_agent();
		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}
		$CI->db->where('cat_parent', $parent);
		
		if ($admin==FALSE)
		{
			$CI->db->where('cat_display', 'Y');	
		}
		$query = $CI->db->get();
		//echo $this->db->last_query();
		foreach ($query->result() as $row)
		{
			/**
			if($this->is_shiftjis($useragent)== true){
			$rs['cat_name']=$this->convertUtfToShiftjis($prefix.$row->cat_name);
			$rs['cat_description']=$this->convertUtfToShiftjis($row->cat_description);
			}else{
			$rs['cat_name']=$prefix . $row->cat_name;
			$rs['cat_description']=$row->cat_description;
			}
			*/
			$rs['cat_name']=$prefix . $row->cat_name;
			$rs['cat_id']=$row->cat_id;
			$rs['cat_uri']=$row->cat_uri;
			$rs['cat_description']=$row->cat_description;
			$id=$row->cat_id;
			if ($article_id <> '')
			{
				$CI->db->from('article2cat')->where('article_id', $article_id)->where('category_id', $row->cat_id);
				$art2cat = $CI->db->get();
				if ($art2cat->num_rows() > 0)
				{
					$rs['selected'] = 'Y';
				}
				else
				{
					$rs['selected'] = 'N';
				}
			}
			else
			{
				$rs['selected'] = 'N';
			}
			array_push($arr, $rs);
			$arr = array_merge($arr, $this->get_cats_for_select_with_site_id($prefix.'&nbsp;&nbsp;&raquo;&nbsp;', $id, $article_id,$admin));
		}
		return $arr;
	}
	function get_most_popular_with_site_id($number=25){
		$CI =& get_instance();
		$number = (int)$number;
		$CI->db->select('article_uri,article_title')->from('articles');
		$CI->db->join('articles2site', 'articles.article_id = articles2site.article_id', 'left');
		$useragent = $CI->input->user_agent();
		if($this->is_mobile($useragent) == false)
		{
		   $CI->db->where('site_id', 1);
		}else {
		   $CI->db->where('site_id', 2);
		}
		$CI->db->where('article_display', 'Y')->orderby('article_hits', 'DESC')->limit($number);
		$query = $CI->db->get();
		return $query;
	}

	function insert_clickinfo($data)
	{
        $CI =& get_instance();
		$CI->db->insert('clickinfo', $data);
        if ($CI->db->affected_rows() > 0){
        	$CI->db->cache_delete_all();
        }
	}

    function insert_rating_log($data){
		$CI =& get_instance();

		$article_id = (int) $data['article_id'];
		$article_rating = (int) $data['rating'];
		$param = array(
				'article_id' => $article_id, 
				'datetime' => date('Y-m-d H:i:s',time()),
				'article_rating'=> $article_rating
		);
		$CI->db->insert('rating_log', $param);
        if ($CI->db->affected_rows() > 0){
        	$CI->db->cache_delete_all();
        }
		if((int) $article_rating == 1){
			$CI->core_events->trigger('thankyou');
			return "Done";
			//$CI->load->helper('url');
		}else{
			$useragent = $CI->input->user_agent();
			if($this->is_mobile($useragent)){
				redirect(''); 
			}else{
				redirect(''); 
			}
		}
	}
}

/* End of file events.php */
/* Location: ./upload/my-modules/fujisan/events.php */ 
