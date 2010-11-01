<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * test Events File
 *
 * The class name must be named "yourmodule_events" where your module is the name
 * of the module. For this module it is named "developer". 
 */
class management_events
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
     $core_events->register('articles/form/description', $this, 'show_article_siteinfo');
     $core_events->register('category/form', $this, 'show_category_siteinfo');
     $core_events->register('category/add', $this, 'add_category_siteinfo');
     $core_events->register('category/edit', $this, 'add_category_siteinfo');
	 $core_events->register('categories/delete', $this, 'delete_categories2fujisan_by_id');
     $core_events->register('show_siteinfo_on_categories', $this, 'show_siteinfo_on_categories');
     $core_events->register('articles/add', $this, 'add_article_siteinfo');
     $core_events->register('articles/edit', $this, 'add_article_siteinfo');
     $core_events->register('articles/delete', $this, 'delete_articles2fujisan_by_id');
     $core_events->register('modulecategories/grid', $this, 'show_siteinfo_on_categories');
     $core_events->register('show_site_id_selection', $this, 'show_site_id_selection');
     $core_events->register('show_siteinfo_on_articles', $this, 'show_siteinfo_on_articles');
     $core_events->register('th_site_info_label', $this, 'th_site_info_label');
	}
	
	// ------------------------------------------------------------------------
	//For Atricle
	function th_site_info_label()
	{
		echo "<th>PC or MOBILE</th>";
	}

	function show_article_siteinfo()
	{
        $CI =& get_instance();
        $id = (int) $CI->uri->segment(4, 0);
        $CI->db->from('articles2fujisan')->where('article_id', $id);
        $query = $CI->db->get();
        echo '<p class="row2">';
        echo '<label for="article_display">Site Info:</label>';
        echo '<select tabindex="6" name="site_id" id="site_id" >';
		if ($query->num_rows() > 0){
			foreach ($query->result() as $row){
           		if((int)$row->site_id == 1){
			        $CI->db->select('site_id,shortname');
			        $CI->db->from('siteinfo');
					$site_infos = $CI->db->get();
		    	    if ($site_infos->num_rows() > 0){
		        		foreach ($site_infos->result() as $site_info){
        	   				if( (int)$site_info->site_id == 0){
          					}
        	   				if( (int)$site_info->site_id == 1){
		               		echo '<option value="'.$site_info->site_id.'" selected>'.$site_info->shortname.'</option>';
							}
							if((int)$site_info->site_id !== 0 && (int)$site_info->site_id !== 1 ){
		               		echo '<option value="'.$site_info->site_id.'" >'.$site_info->shortname.'</option>';
							}
        				}
					}else{
               		echo '<option value="1" selected>PC</option>';
               		echo '<option value="2">Mobile</option>';
					}
          		}
          		if( (int)$row->site_id == 2){
			        $CI->db->select('site_id,shortname');
			        $CI->db->from('siteinfo');
					$site_infos = $CI->db->get();
		    	    if ($site_infos->num_rows() > 0){
		        		foreach ($site_infos->result() as $site_info){
        	   				if( (int)$site_info->site_id == 0){
          					}
        	   				if( (int)$site_info->site_id == 2){
		               		echo '<option value="'.$site_info->site_id.'" selected>'.$site_info->shortname.'</option>';
							}
							if((int)$site_info->site_id !== 0 && (int)$site_info->site_id !== 2 ){
		               		echo '<option value="'.$site_info->site_id.'" >'.$site_info->shortname.'</option>';
							}
        				}
					}else{
               		echo '<option value="1">PC</option>';
               		echo '<option value="2" selected>Mobile</option>';
					}
          		}
          		if( (int) $row->site_id !== 2 && (int)$row->site_id !== 1 ){
          		echo '<option value="2" >Mobile</option>';
          		echo '<option value="1" >PC</option>';
         		}
        	}
        }else{
          echo '<option value="1" >PC</option>';
          echo '<option value="2" >Mobile</option>';
        }
        echo  '</select>';
        echo '</p>';
	}

	function show_siteinfo_on_articles($article_id)
	{
        $CI =& get_instance();
        $id = (int) $article_id;
        $CI->db->from('articles2fujisan')->where('article_id', $id);
        $query = $CI->db->get();
		echo "<td>";
        if ($query->num_rows() > 0){
        	foreach ($query->result() as $row){
				if( $row->site_id == null || ($row->site_id !== null && (int)$row->site_id == 0)){
					echo "N/A";
				}
			    $CI->db->select('shortname');
			    $CI->db->from('siteinfo');
				$CI->db->where('site_id',(int)$row->site_id);
				$site_infos = $CI->db->get();
		    	if ($site_infos->num_rows() > 0){
		        	foreach ($site_infos->result() as $site_info){
		             	echo "$site_info->shortname";
					}
				}
        	}
        }else{
               	echo "N/A";
        }
		echo "</td>";
	}

	function add_article_siteinfo($id){
        $CI =& get_instance();
		$data = array(
			'article_id' => (int) $id, 
			'site_id' => (int) $CI->input->post('site_id', TRUE)
		);
		$this -> insert_or_update_articles2fujisan($data);
	}

	function delete_articles2fujisan_by_id($id){
        $CI =& get_instance();
        $id = (int) $id; 
        $CI->db->from('articles2fujisan')->where('article_id', $id);
        $query = $CI->db->get();
        if ($query->num_rows() > 0){
			$CI->db->delete('articles2fujisan', array('article_id' => $id)); 
			if ($CI->db->affected_rows() > 0) 
			{
				$CI->db->cache_delete_all();
			} 
        }
	}

	function insert_or_update_articles2fujisan($data)
	{
        $CI =& get_instance();
        $id = (int) $data['article_id']; 
        $CI->db->from('articles2fujisan')->where('article_id', $id);
        $query = $CI->db->get();
        if ($query->num_rows() > 0){
        	$CI->db->where('article_id', $id);
        	$CI->db->update('articles2fujisan', $data);
        	if ($CI->db->affected_rows() > 0){
        	$CI->db->cache_delete_all();
        	}
        }else{
        $CI->db->insert('articles2fujisan', $data);
        	if ($CI->db->affected_rows() > 0){
        	$CI->db->cache_delete_all();
        	}
	    }
	}

	//For Category
	function show_siteinfo_on_categories($category_id)
	{
        $CI =& get_instance();
        $id = (int) $category_id;
        $CI->db->from('categories2fujisan')->where('category_id', $id);
        $query = $CI->db->get();
		echo "<td>";
        if ($query->num_rows() > 0){
        	foreach ($query->result() as $row){
				if( $row->site_id == null || ($row->site_id !== null && (int)$row->site_id == 0)){
					echo "N/A";
				}
			    $CI->db->select('shortname');
			    $CI->db->from('siteinfo');
				$CI->db->where('site_id',(int)$row->site_id);
				$site_infos = $CI->db->get();
		    	if ($site_infos->num_rows() > 0){
		        	foreach ($site_infos->result() as $site_info){
		             	echo "$site_info->shortname";
					}
				}
        	}
        }else{
               	echo "N/A";
        }
		echo "</td>";
	}

    function show_category_siteinfo()
    {
        $CI =& get_instance();
        $id = (int) $CI->uri->segment(4, 0);
        $CI->db->from('categories2fujisan')->where('category_id', $id);
        $query = $CI->db->get();
        echo '<p class="row2">';
        echo '<label for="category_display">PC or Mobile:</label>';
        echo '<select tabindex="6" name="site_id" id="site_id" >';
		if ($query->num_rows() > 0){
			foreach ($query->result() as $row){
           		if((int)$row->site_id == 1){
			        $CI->db->select('site_id,shortname');
			        $CI->db->from('siteinfo');
					$site_infos = $CI->db->get();
		    	    if ($site_infos->num_rows() > 0){
		        		foreach ($site_infos->result() as $site_info){
        	   				if( (int)$site_info->site_id == 0){
          					}
        	   				if( (int)$site_info->site_id == 1){
		               		echo '<option value="'.$site_info->site_id.'" selected>'.$site_info->shortname.'</option>';
							}
							if((int)$site_info->site_id !== 0 && (int)$site_info->site_id !== 1 ){
		               		echo '<option value="'.$site_info->site_id.'" >'.$site_info->shortname.'</option>';
							}
        				}
					}else{
               		echo '<option value="1" selected>PC</option>';
               		echo '<option value="2">Mobile</option>';
					}
          		}
          		if( (int)$row->site_id == 2){
			        $CI->db->select('site_id,shortname');
			        $CI->db->from('siteinfo');
					$site_infos = $CI->db->get();
		    	    if ($site_infos->num_rows() > 0){
		        		foreach ($site_infos->result() as $site_info){
        	   				if( (int)$site_info->site_id == 0){
          					}
        	   				if( (int)$site_info->site_id == 2){
		               		echo '<option value="'.$site_info->site_id.'" selected>'.$site_info->shortname.'</option>';
							}
							if((int)$site_info->site_id !== 0 && (int)$site_info->site_id !== 2 ){
		               		echo '<option value="'.$site_info->site_id.'" >'.$site_info->shortname.'</option>';
							}
        				}
					}else{
               		echo '<option value="1">PC</option>';
               		echo '<option value="2" selected>Mobile</option>';
					}
          		}
          		if( (int) $row->site_id !== 2 && (int)$row->site_id !== 1 ){
          		echo '<option value="2" >Mobile</option>';
          		echo '<option value="1" >PC</option>';
         		}
        	}
        }else{
          echo '<option value="1" >PC</option>';
          echo '<option value="2" >Mobile</option>';
        }
        echo  '</select>';
        echo '</p>';
	} 

	function add_category_siteinfo($id){
        $CI =& get_instance(); 
		$data = array(
			'site_id' => (int) $CI->input->post('site_id', TRUE),
			'category_id' => (int) $id
		);
        $this -> insert_or_update_categories2fujisan($data);
	}

	function delete_categories2fujisan_by_id($id){
        $CI =& get_instance(); 
        $id = (int) $id; 
        $CI->db->from('categories2fujisan')->where('category_id', $id);
        $query = $CI->db->get();
        if ($query->num_rows() > 0){
			$CI->db->delete('categories2fujisan', array('category_id' => $id)); 
			if ($CI->db->affected_rows() > 0) 
			{
				$CI->db->cache_delete_all();
			} 
        } 
	}

	function insert_or_update_categories2fujisan($data)
	{
        $CI =& get_instance();
        $id = (int) $data['category_id'];
        $CI->db->from('categories2fujisan')->where('category_id', $id);
        $query = $CI->db->get();
        if ($query->num_rows() > 0){
        	$CI->db->where('category_id', $id);
        	$CI->db->update('categories2fujisan', $data);
        	if ($CI->db->affected_rows() > 0){
        	$CI->db->cache_delete_all();
        	}
        }else{
			$CI->db->insert('categories2fujisan', $data);
        	if ($CI->db->affected_rows() > 0){
        	$CI->db->cache_delete_all();
        	}
	 }
	}

    function show_site_id_selection(){
        $selectionarray = array();
		$CI =& get_instance();
		$CI->load->helper('form');
		$CI->db->select('site_id,shortname');
		$query = $CI->db->get('siteinfo');
		if ($query->num_rows >0){
			foreach($query->result() as $row){
				$selectionarray[$row->site_id] = $row->shortname;
			}
			
		}
		echo '<td>';
		echo form_dropdown('a_site_id',$selectionarray,'0');			
		echo '</td>';
	}

}

/* End of file events.php */
/* Location: ./upload/my-modules/management/events.php */ 
