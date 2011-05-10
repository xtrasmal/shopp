<?php

add_filter('shoppapi_category_carousel', 'carousel', 10, 3);
add_filter('shoppapi_category_coverimage', 'coverimage', 10, 3);
add_filter('shoppapi_category_description', 'description', 10, 3);
add_filter('shoppapi_category_facetedmenu', 'facetedmenu', 10, 3);
add_filter('shoppapi_category_feedurl', 'feedurl', 10, 3);
add_filter('shoppapi_category_hascategories', 'hascategories', 10, 3);
add_filter('shoppapi_category_hasfacetedmenu', 'hasfacetedmenu', 10, 3);
add_filter('shoppapi_category_hasimages', 'hasimages', 10, 3);
add_filter('shoppapi_category_hasproducts', 'hasproducts', 10, 3);
add_filter('shoppapi_category_hasproducts', 'loadproducts', 10, 3);
add_filter('shoppapi_category_id', 'id', 10, 3);
add_filter('shoppapi_category_image', 'image', 10, 3);
add_filter('shoppapi_category_images', 'images', 10, 3);
add_filter('shoppapi_category_issubcategory', 'issubcategory', 10, 3);
add_filter('shoppapi_category_link', 'url', 10, 3);
add_filter('shoppapi_category_loadproducts', 'loadproducts', 10, 3);
add_filter('shoppapi_category_name', 'name', 10, 3);
add_filter('shoppapi_category_pagination', 'pagination', 10, 3);
add_filter('shoppapi_category_parent', 'parent', 10, 3);
add_filter('shoppapi_category_products', 'products', 10, 3);
add_filter('shoppapi_category_row', 'row', 10, 3);
add_filter('shoppapi_category_sectionlist', 'sectionlist', 10, 3);
add_filter('shoppapi_category_slideshow', 'slideshow', 10, 3);
add_filter('shoppapi_category_slug', 'slug', 10, 3);
add_filter('shoppapi_category_subcategories', 'subcategories', 10, 3);
add_filter('shoppapi_category_subcategorylist', 'subcategorylist', 10, 3);
add_filter('shoppapi_category_total', 'total', 10, 3);
add_filter('shoppapi_category_url', 'url', 10, 3);

/**
 * shopp('category','...') tags
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.0
 * @version 1.1
 * @see http://docs.shopplugin.net/Category_Tags
 *
 **/
class ShoppCategoryAPI {
	function carousel ($result, $options, $obj) {
		$options['load'] = array('images');
		if (!$obj->loaded) $obj->load_products($options);
		if (count($obj->products) == 0) return false;

		$defaults = array(
			'imagewidth' => '96',
			'imageheight' => '96',
			'fit' => 'all',
			'duration' => 500
		);
		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		$string = '<div class="carousel duration-'.$duration.'">';
		$string .= '<div class="frame">';
		$string .= '<ul>';
		foreach ($obj->products as $Product) {
			if (empty($Product->images)) continue;
			$string .= '<li><a href="'.$Product->tag('url').'">';
			$string .= $Product->tag('image',array('width'=>$imagewidth,'height'=>$imageheight,'fit'=>$fit));
			$string .= '</a></li>';
		}
		$string .= '</ul></div>';
		$string .= '<button type="button" name="left" class="left">&nbsp;</button>';
		$string .= '<button type="button" name="right" class="right">&nbsp;</button>';
		$string .= '</div>';
		return $string;
	}

	function coverimage ($result, $options, $obj) {
		unset($options['id']);
		$options['index'] = 0;
		return self::image($result, $options, $obj);
	}

	function description ($result, $options, $obj) { return wpautop($obj->description); }

	function facetedmenu ($result, $options, $obj) {
		global $Shopp;
		$db = DB::get();

		if ($obj->facetedmenus == "off") return;
		$output = "";
		$CategoryFilters =& $Shopp->Flow->Controller->browsing[$obj->slug];
		$link = $_SERVER['REQUEST_URI'];
		if (!isset($options['cancel'])) $options['cancel'] = "X";
		if (strpos($_SERVER['REQUEST_URI'],"?") !== false)
			list($link,$query) = explode("?",$_SERVER['REQUEST_URI']);
		$query = $_GET;
		$query = http_build_query($query);
		$link = esc_url($link).'?'.$query;

		$list = "";
		if (is_array($CategoryFilters)) {
			foreach($CategoryFilters AS $facet => $filter) {
				$href = add_query_arg('shopp_catfilters['.urlencode($facet).']','',$link);
				if (preg_match('/^(.*?(\d+[\.\,\d]*).*?)\-(.*?(\d+[\.\,\d]*).*)$/',$filter,$matches)) {
					$label = $matches[1].' &mdash; '.$matches[3];
					if ($matches[2] == 0) $label = __('Under ','Shopp').$matches[3];
					if ($matches[4] == 0) $label = $matches[1].' '.__('and up','Shopp');
				} else $label = $filter;
				if (!empty($filter)) $list .= '<li><strong>'.$facet.'</strong>: '.stripslashes($label).' <a href="'.$href.'=" class="cancel">'.$options['cancel'].'</a></li>';
			}
			$output .= '<ul class="filters enabled">'.$list.'</ul>';
		}

		if ($obj->pricerange == "auto" && empty($CategoryFilters['Price'])) {
			// if (!$obj->loaded) $obj->load_products();
			$list = "";
			$obj->priceranges = auto_ranges($obj->pricing['average'],$obj->pricing['max'],$obj->pricing['min']);
			foreach ($obj->priceranges as $range) {
				$href = add_query_arg('shopp_catfilters[Price]',urlencode(money($range['min']).'-'.money($range['max'])),$link);
				$label = money($range['min']).' &mdash; '.money($range['max']-0.01);
				if ($range['min'] == 0) $label = __('Under ','Shopp').money($range['max']);
				elseif ($range['max'] == 0) $label = money($range['min']).' '.__('and up','Shopp');
				$list .= '<li><a href="'.$href.'">'.$label.'</a></li>';
			}
			if (!empty($obj->priceranges)) $output .= '<h4>'.__('Price Range','Shopp').'</h4>';
			$output .= '<ul>'.$list.'</ul>';
		}

		$catalogtable = DatabaseObject::tablename(Catalog::$table);
		$producttable = DatabaseObject::tablename(Product::$table);
		$spectable = DatabaseObject::tablename(Spec::$table);

		$query = "SELECT spec.name,spec.value,
			IF(spec.numeral > 0,spec.name,spec.value) AS merge,
			count(*) AS total,avg(numeral) AS avg,max(numeral) AS max,min(numeral) AS min
			FROM $catalogtable AS cat
			LEFT JOIN $producttable AS p ON cat.product=p.id
			LEFT JOIN $spectable AS spec ON p.id=spec.parent AND spec.context='product' AND spec.type='spec'
			WHERE cat.parent=$obj->id AND cat.taxonomy='$obj->taxonomy' AND spec.value != '' AND spec.value != '0' GROUP BY merge ORDER BY spec.name,merge";

		$results = $db->query($query,AS_ARRAY);

		$specdata = array();
		foreach ($results as $data) {
			if (isset($specdata[$data->name])) {
				if (!is_array($specdata[$data->name]))
					$specdata[$data->name] = array($specdata[$data->name]);
				$specdata[$data->name][] = $data;
			} else $specdata[$data->name] = $data;
		}

		if (is_array($obj->specs)) {
			foreach ($obj->specs as $spec) {
				$list = "";
				if (!empty($CategoryFilters[$spec['name']])) continue;

				// For custom menu presets
				if ($spec['facetedmenu'] == "custom" && !empty($spec['options'])) {
					foreach ($spec['options'] as $option) {
						$href = add_query_arg('shopp_catfilters['.$spec['name'].']',urlencode($option['name']),$link);
						$list .= '<li><a href="'.$href.'">'.$option['name'].'</a></li>';
					}
					$output .= '<h4>'.$spec['name'].'</h4><ul>'.$list.'</ul>';

				// For preset ranges
				} elseif ($spec['facetedmenu'] == "ranges" && !empty($spec['options'])) {
					foreach ($spec['options'] as $i => $option) {
						$matches = array();
						$format = '%s-%s';
						$next = 0;
						if (isset($spec['options'][$i+1])) {
							if (preg_match('/(\d+[\.\,\d]*)/',$spec['options'][$i+1]['name'],$matches))
								$next = $matches[0];
						}
						$matches = array();
						$range = array("min" => 0,"max" => 0);
						if (preg_match('/^(.*?)(\d+[\.\,\d]*)(.*)$/',$option['name'],$matches)) {
							$base = $matches[2];
							$format = $matches[1].'%s'.$matches[3];
							if (!isset($spec['options'][$i+1])) $range['min'] = $base;
							else $range = array("min" => $base, "max" => ($next-1));
						}
						if ($i == 1) {
							$href = add_query_arg('shopp_catfilters['.$spec['name'].']', urlencode(sprintf($format,'0',$range['min'])),$link);
							$label = __('Under ','Shopp').sprintf($format,$range['min']);
							$list .= '<li><a href="'.$href.'">'.$label.'</a></li>';
						}

						$href = add_query_arg('shopp_catfilters['.$spec['name'].']', urlencode(sprintf($format,$range['min'],$range['max'])), $link);
						$label = sprintf($format,$range['min']).' &mdash; '.sprintf($format,$range['max']);
						if ($range['max'] == 0) $label = sprintf($format,$range['min']).' '.__('and up','Shopp');
						$list .= '<li><a href="'.$href.'">'.$label.'</a></li>';
					}
					$output .= '<h4>'.$spec['name'].'</h4><ul>'.$list.'</ul>';

				// For automatically building the menu options
				} elseif ($spec['facetedmenu'] == "auto" && isset($specdata[$spec['name']])) {

					if (is_array($specdata[$spec['name']])) { // Generate from text values
						foreach ($specdata[$spec['name']] as $option) {
							$href = add_query_arg('shopp_catfilters['.$spec['name'].']',urlencode($option->value),$link);
							$list .= '<li><a href="'.$href.'">'.$option->value.'</a></li>';
						}
						$output .= '<h4>'.$spec['name'].'</h4><ul>'.$list.'</ul>';
					} else { // Generate number ranges
						$format = '%s';
						if (preg_match('/^(.*?)(\d+[\.\,\d]*)(.*)$/',$specdata[$spec['name']]->content,$matches))
							$format = $matches[1].'%s'.$matches[3];

						$ranges = auto_ranges($specdata[$spec['name']]->avg,$specdata[$spec['name']]->max,$specdata[$spec['name']]->min);
						foreach ($ranges as $range) {
							$href = add_query_arg('shopp_catfilters['.$spec['name'].']', urlencode($range['min'].'-'.$range['max']), $link);
							$label = sprintf($format,$range['min']).' &mdash; '.sprintf($format,$range['max']);
							if ($range['min'] == 0) $label = __('Under ','Shopp').sprintf($format,$range['max']);
							elseif ($range['max'] == 0) $label = sprintf($format,$range['min']).' '.__('and up','Shopp');
							$list .= '<li><a href="'.$href.'">'.$label.'</a></li>';
						}
						if (!empty($list)) $output .= '<h4>'.$spec['name'].'</h4>';
						$output .= '<ul>'.$list.'</ul>';

					}
				}
			}
		}


		return $output;
	}

	function feedurl ($result, $options, $obj) {
		$uri = 'category/'.$obj->uri;
		if ($obj->slug == "tag") $uri = $obj->slug.'/'.$obj->tag;
		return shoppurl(SHOPP_PRETTYURLS?"$uri/feed":array('s_cat'=>urldecode($obj->uri),'src'=>'category_rss'));
	}

	function hascategories ($result, $options, $obj) {
		if (empty($obj->children)) $obj->load_children();
		return (!empty($obj->children));
	}

	function hasfacetedmenu ($result, $options, $obj) { return ($obj->facetedmenus == "on"); }

	function hasimages ($result, $options, $obj) {
		if (empty($obj->images)) $obj->load_images();
		if (empty($obj->images)) return false;
		return true;
	}

	function hasproducts ($result, $options, $obj) {}

	function id ($result, $options, $obj) { return $obj->id; }

	function image ($result, $options, $obj) {
		global $Shopp;
		if (empty($obj->images)) $obj->load_images();
		if (!(count($obj->images) > 0)) return "";

		// Compatibility defaults
		$_size = 96;
		$_width = $Shopp->Settings->get('gallery_thumbnail_width');
		$_height = $Shopp->Settings->get('gallery_thumbnail_height');
		if (!$_width) $_width = $_size;
		if (!$_height) $_height = $_size;

		$defaults = array(
			'img' => false,
			'id' => false,
			'index' => false,
			'class' => '',
			'width' => false,
			'height' => false,
			'width_a' => false,
			'height_a' => false,
			'size' => false,
			'fit' => false,
			'sharpen' => false,
			'quality' => false,
			'bg' => false,
			'alt' => '',
			'title' => '',
			'zoom' => '',
			'zoomfx' => 'shopp-zoom',
			'property' => false
		);
		$options = array_merge($defaults,$options);
		extract($options);

		// Select image by database id
		if ($id !== false) {
			for ($i = 0; $i < count($obj->images); $i++) {
				if ($img->id == $id) {
					$img = $obj->images[$i]; //break;
				}
			}
			if (!$img) return "";
		}

		// Select image by index position in the list
		if ($index !== false && isset($obj->images[$index]))
			$img = $obj->images[$index];

		// Use the current image pointer by default
		if (!$img) $img = current($obj->images);

		if ($size !== false) $width = $height = $size;
		if (!$width) $width = $_width;
		if (!$height) $height = $_height;

		$scale = $fit?array_search($fit,$img->_scaling):false;
		$sharpen = $sharpen?min($sharpen,$img->_sharpen):false;
		$quality = $quality?min($quality,$img->_quality):false;
		$fill = $bg?hexdec(ltrim($bg,'#')):false;

		list($width_a,$height_a) = array_values($img->scaled($width,$height,$scale));
		if ($size == "original") {
			$width_a = $img->width;
			$height_a = $img->height;
		}
		if ($width_a === false) $width_a = $width;
		if ($height_a === false) $height_a = $height;

		$alt = esc_attr(empty($alt)?(empty($img->alt)?$img->name:$img->alt):$alt);
		$title = empty($title)?$img->title:$title;
		$titleattr = empty($title)?'':' title="'.esc_attr($title).'"';
		$classes = empty($class)?'':' class="'.esc_attr($class).'"';

		$src = shoppurl($img->id,'images');
		if ($size != "original") {
			$src = add_query_string(
				$img->resizing($width,$height,$scale,$sharpen,$quality,$fill),
				trailingslashit(shoppurl($img->id,'images')).$img->filename
			);
		}

		switch (strtolower($property)) {
			case 'id': return $img->id; break;
			case 'url':
			case 'src': return $src; break;
			case 'title': return $title; break;
			case 'alt': return $alt; break;
			case 'width': return $width_a; break;
			case 'height': return $height_a; break;
			case 'class': return $class; break;
		}

		$imgtag = '<img src="'.$src.'"'.$titleattr.' alt="'.$alt.'" width="'.$width_a.'" height="'.$height_a.'" '.$classes.' />';

		if (value_is_true($zoom))
			return '<a href="'.shoppurl($img->id,'images').'/'.$img->filename.'" class="'.$zoomfx.'" rel="product-'.$obj->id.'">'.$imgtag.'</a>';

		return $imgtag;
	}

	function images ($result, $options, $obj) {
		if (!isset($obj->_images_loop)) {
			reset($obj->images);
			$obj->_images_loop = true;
		} else next($obj->images);

		if (current($obj->images) !== false) return true;
		else {
			unset($obj->_images_loop);
			return false;
		}
	}

	function issubcategory ($result, $options, $obj) { return ($obj->parent != 0); }

	function loadproducts ($result, $options, $obj) {
		if (empty($obj->id) && empty($obj->slug)) return false;
		if (isset($options['load'])) {
			$dataset = explode(",",$options['load']);
			$options['load'] = array();
			foreach ($dataset as $name) $options['load'][] = trim($name);
		 } else {
			$options['load'] = array('prices');
		}
		if (!$obj->loaded) $obj->load_products($options);
		if (count($obj->products) > 0) return true; else return false;
	}

	function name ($result, $options, $obj) { return $obj->name; }

	function pagination ($result, $options, $obj) {
		if (!$obj->paged) return "";

		$defaults = array(
			'label' => __("Pages:","Shopp"),
			'next' => __("next","Shopp"),
			'previous' => __("previous","Shopp"),
			'jumpback' => '&laquo;',
			'jumpfwd' => '&raquo;',
			'show' => 1000,
			'before' => '<div>',
			'after' => '</div>'
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$_ = array();
		if (isset($obj->alpha) && $obj->paged) {
			$_[] = $before.$label;
			$_[] = '<ul class="paging">';
			foreach ($obj->alpha as $alpha) {
				$link = $obj->pagelink($alpha->letter);
				if ($alpha->total > 0)
					$_[] = '<li><a href="'.$link.'">'.$alpha->letter.'</a></li>';
				else $_[] = '<li><span>'.$alpha->letter.'</span></li>';
			}
			$_[] = '</ul>';
			$_[] = $after;
			return join("\n",$_);
		}

		if ($obj->pages > 1) {

			if ( $obj->pages > $show ) $visible_pages = $show + 1;
			else $visible_pages = $obj->pages + 1;
			$jumps = ceil($visible_pages/2);
			$_[] = $before.$label;

			$_[] = '<ul class="paging">';
			if ( $obj->page <= floor(($show) / 2) ) {
				$i = 1;
			} else {
				$i = $obj->page - floor(($show) / 2);
				$visible_pages = $obj->page + floor(($show) / 2) + 1;
				if ($visible_pages > $obj->pages) $visible_pages = $obj->pages + 1;
				if ($i > 1) {
					$link = $obj->pagelink(1);
					$_[] = '<li><a href="'.$link.'">1</a></li>';

					$pagenum = ($obj->page - $jumps);
					if ($pagenum < 1) $pagenum = 1;
					$link = $obj->pagelink($pagenum);
					$_[] = '<li><a href="'.$link.'">'.$jumpback.'</a></li>';
				}
			}

			// Add previous button
			if (!empty($previous) && $obj->page > 1) {
				$prev = $obj->page-1;
				$link = $obj->pagelink($prev);
				$_[] = '<li class="previous"><a href="'.$link.'">'.$previous.'</a></li>';
			} else $_[] = '<li class="previous disabled">'.$previous.'</li>';
			// end previous button

			while ($i < $visible_pages) {
				$link = $obj->pagelink($i);
				if ( $i == $obj->page ) $_[] = '<li class="active">'.$i.'</li>';
				else $_[] = '<li><a href="'.$link.'">'.$i.'</a></li>';
				$i++;
			}
			if ($obj->pages > $visible_pages) {
				$pagenum = ($obj->page + $jumps);
				if ($pagenum > $obj->pages) $pagenum = $obj->pages;
				$link = $obj->pagelink($pagenum);
				$_[] = '<li><a href="'.$link.'">'.$jumpfwd.'</a></li>';
				$link = $obj->pagelink($obj->pages);
				$_[] = '<li><a href="'.$link.'">'.$obj->pages.'</a></li>';
			}

			// Add next button
			if (!empty($next) && $obj->page < $obj->pages) {
				$pagenum = $obj->page+1;
				$link = $obj->pagelink($pagenum);
				$_[] = '<li class="next"><a href="'.$link.'">'.$next.'</a></li>';
			} else $_[] = '<li class="next disabled">'.$next.'</li>';

			$_[] = '</ul>';
			$_[] = $after;
		}
		return join("\n",$_);
	}

	function parent ($result, $options, $obj) { return $obj->parent; }

	function products ($result, $options, $obj) {
		global $Shopp;
		if (!isset($obj->_product_loop)) {
			reset($obj->products);
			$Shopp->Product = current($obj->products);
			$obj->_pindex = 0;
			$obj->_rindex = false;
			$obj->_product_loop = true;
		} else {
			$Shopp->Product = next($obj->products);
			$obj->_pindex++;
		}

		if (current($obj->products) !== false) return true;
		else {
			unset($obj->_product_loop);
			$obj->_pindex = 0;
			return false;
		}
	}

	function row ($result, $options, $obj) {
		global $Shopp;
		if (!isset($obj->_rindex) || $obj->_rindex === false) $obj->_rindex = 0;
		else $obj->_rindex++;
		if (empty($options['products'])) $options['products'] = $Shopp->Settings->get('row_products');
		if (isset($obj->_rindex) && $obj->_rindex > 0 && $obj->_rindex % $options['products'] == 0) return true;
		else return false;
	}

	function sectionlist ($result, $options, $obj) {
		global $Shopp;
		if (empty($obj->id)) return false;
		if (isset($Shopp->Category->controls)) return false;
		if (empty($Shopp->Catalog->categories))
			$Shopp->Catalog->load_categories(array("where"=>"(pd.status='publish' OR pd.id IS NULL)"));
		if (empty($Shopp->Catalog->categories)) return false;
		if (!$obj->children) $obj->load_children();

		$defaults = array(
			'title' => '',
			'before' => '',
			'after' => '',
			'class' => '',
			'classes' => '',
			'exclude' => '',
			'total' => '',
			'current' => '',
			'listing' => '',
			'depth' => 0,
			'parent' => false,
			'showall' => false,
			'linkall' => false,
			'dropdown' => false,
			'hierarchy' => false,
			'products' => false,
			'wraplist' => true
			);

		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		$string = "";
		$depthlimit = $depth;
		$depth = 0;
		$wraplist = value_is_true($wraplist);
		$exclude = explode(",",$exclude);
		$section = array();

		// Identify root parent
		if (empty($obj->id)) return false;
		$parent = '_'.$obj->id;
		while($parent != 0) {
			if (!isset($Shopp->Catalog->categories[$parent])) //break;
			if ($Shopp->Catalog->categories[$parent]->parent == 0
				|| $Shopp->Catalog->categories[$parent]->parent == $parent) //break;
			$parent = '_'.$Shopp->Catalog->categories[$parent]->parent;
		}
		$root = $Shopp->Catalog->categories[$parent];
		if ($obj->id == $parent && empty($obj->children)) return false;

		// Build the section
		$section[] = $root;
		$in = false;
		foreach ($Shopp->Catalog->categories as &$c) {
			if ($in && $c->depth == $root->depth) //break; // Done
			if ($in) $section[] = $c;
			if (!$in && isset($c->id) && $c->id == $root->id) $in = true;
		}

		if (value_is_true($dropdown)) {
			$string .= $title;
			$string .= '<select name="shopp_cats" id="shopp-'.$obj->slug.'-subcategories-menu" class="shopp-categories-menu">';
			$string .= '<option value="">'.__('Select a sub-category&hellip;','Shopp').'</option>';
			foreach ($section as &$category) {
				if (value_is_true($hierarchy) && $depthlimit && $category->depth >= $depthlimit) continue;
				if (in_array($category->id,$exclude)) continue; // Skip excluded categories
				if ($category->products == 0) continue; // Only show categories with products
				if (value_is_true($hierarchy) && $category->depth > $depth) {
					$parent = &$previous;
					if (!isset($parent->path)) $parent->path = '/'.$parent->slug;
				}
				$padding = str_repeat("&nbsp;",$category->depth*3);

				$category_uri = empty($category->id)?$category->uri:$category->id;
				$link = SHOPP_PRETTYURLS?shoppurl("category/$category->uri"):shoppurl(array('s_cat'=>$category_uri));

				$total = '';
				if (value_is_true($products)) $total = '&nbsp;&nbsp;('.$category->total.')';

				$string .= '<option value="'.htmlentities($link).'">'.$padding.$category->name.$total.'</option>';
				$previous = &$category;
				$depth = $category->depth;

			}
			$string .= '</select>';
		} else {
			if (!empty($class)) $classes = ' class="'.$class.'"';
			$string .= $title;
			if ($wraplist) $string .= '<ul'.$classes.'>';
			foreach ($section as &$category) {
				if (in_array($category->id,$exclude)) continue; // Skip excluded categories
				if (value_is_true($hierarchy) && $depthlimit &&
					$category->depth >= $depthlimit) continue;
				if (value_is_true($hierarchy) && $category->depth > $depth) {
					$parent = &$previous;
					if (!isset($parent->path) && isset($parent->slug)) $parent->path = $parent->slug;
					$string = substr($string,0,-5);
					$string .= '<ul class="children">';
				}
				if (value_is_true($hierarchy) && $category->depth < $depth) $string .= '</ul></li>';

				$category_uri = empty($category->id)?$category->uri:$category->id;
				$link = SHOPP_PRETTYURLS?shoppurl("category/$category->uri"):shoppurl(array('s_cat'=>$category_uri));

				if (value_is_true($products)) $total = ' <span>('.$category->total.')</span>';

				if ($category->total > 0 || isset($category->smart) || $linkall) $listing = '<a href="'.$link.'"'.$current.'>'.$category->name.$total.'</a>';
				else $listing = $category->name;

				if (value_is_true($showall) ||
					$category->total > 0 ||
					$category->children)
					$string .= '<li>'.$listing.'</li>';

				$previous = &$category;
				$depth = $category->depth;
			}
			if (value_is_true($hierarchy) && $depth > 0)
				for ($i = $depth; $i > 0; $i--) $string .= '</ul></li>';

			if ($wraplist) $string .= '</ul>';
		}
		return $string;
	}

	function slideshow ($result, $options, $obj) {
		$options['load'] = array('images');
		if (!$obj->loaded) $obj->load_products($options);
		if (count($obj->products) == 0) return false;

		$defaults = array(
			'width' => '440',
			'height' => '180',
			'fit' => 'crop',
			'fx' => 'fade',
			'duration' => 1000,
			'delay' => 7000,
			'order' => 'normal'
		);
		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		$href = shoppurl(SHOPP_PERMALINKS?trailingslashit('000'):'000','images');
		$imgsrc = add_query_string("$width,$height",$href);

		$string = '<ul class="slideshow '.$fx.'-fx '.$order.'-order duration-'.$duration.' delay-'.$delay.'">';
		$string .= '<li class="clear"><img src="'.$imgsrc.'" width="'.$width.'" height="'.$height.'" /></li>';
		foreach ($obj->products as $Product) {
			if (empty($Product->images)) continue;
			$string .= '<li><a href="'.$Product->tag('url').'">';
			$string .= $Product->tag('image',array('width'=>$width,'height'=>$height,'fit'=>$fit));
			$string .= '</a></li>';
		}
		$string .= '</ul>';
		return $string;
	}

	function slug ($result, $options, $obj) { return urldecode($obj->slug); }

	function subcategories ($result, $options, $obj) {
		if (!isset($obj->_children_loop)) {
			reset($obj->children);
			$obj->child = current($obj->children);
			$obj->_cindex = 0;
			$obj->_children_loop = true;
		} else {
			$obj->child = next($obj->children);
			$obj->_cindex++;
		}

		if ($obj->child !== false) return true;
		else {
			unset($obj->_children_loop);
			$obj->_cindex = 0;
			$obj->child = false;
			return false;
		}
	}

	function subcategorylist ($result, $options, $obj) {
		global $Shopp;
		if (isset($Shopp->Category->controls)) return false;

		$defaults = array(
			'title' => '',
			'before' => '',
			'after' => '',
			'class' => '',
			'exclude' => '',
			'orderby' => 'name',
			'order' => 'ASC',
			'depth' => 0,
			'childof' => 0,
			'parent' => false,
			'showall' => false,
			'linkall' => false,
			'linkcount' => false,
			'dropdown' => false,
			'hierarchy' => false,
			'products' => false,
			'wraplist' => true,
			'showsmart' => false
			);

		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		if (!$obj->children) $obj->load_children(array('orderby'=>$orderby,'order'=>$order));
		if (empty($obj->children)) return false;

		$string = "";
		$depthlimit = $depth;
		$depth = 0;
		$exclude = explode(",",$exclude);
		$classes = ' class="shopp_categories'.(empty($class)?'':' '.$class).'"';
		$wraplist = value_is_true($wraplist);

		if (value_is_true($dropdown)) {
			$count = 0;
			$string .= $title;
			$string .= '<select name="shopp_cats" id="shopp-'.$obj->slug.'-subcategories-menu" class="shopp-categories-menu">';
			$string .= '<option value="">'.__('Select a sub-category&hellip;','Shopp').'</option>';
			foreach ($obj->children as &$category) {
				if (!empty($show) && $count+1 > $show) //break;
				if (value_is_true($hierarchy) && $depthlimit && $category->depth >= $depthlimit) continue;
				if ($category->products == 0) continue; // Only show categories with products
				if (value_is_true($hierarchy) && $category->depth > $depth) {
					$parent = &$previous;
					if (!isset($parent->path)) $parent->path = '/'.$parent->slug;
				}
				$padding = str_repeat("&nbsp;",$category->depth*3);

				$category_uri = empty($category->id)?$category->uri:$category->id;
				$link = SHOPP_PRETTYURLS?shoppurl("category/$category->uri"):shoppurl(array('s_cat'=>$category_uri));

				$total = '';
				if (value_is_true($products)) $total = '&nbsp;&nbsp;('.$category->products.')';

				$string .= '<option value="'.htmlentities($link).'">'.$padding.$category->name.$total.'</option>';
				$previous = &$category;
				$depth = $category->depth;
				$count++;
			}
			$string .= '</select>';
		} else {
			if (!empty($class)) $classes = ' class="'.$class.'"';
			$string .= $title.'<ul'.$classes.'>';
			$count = 0;
			foreach ($obj->children as &$category) {
				if (!isset($category->total)) $category->total = 0;
				if (!isset($category->depth)) $category->depth = 0;
				if (!empty($category->id) && in_array($category->id,$exclude)) continue; // Skip excluded categories
				if ($depthlimit && $category->depth >= $depthlimit) continue;
				if (value_is_true($hierarchy) && $category->depth > $depth) {
					$parent = &$previous;
					if (!isset($parent->path)) $parent->path = $parent->slug;
					$string = substr($string,0,-5); // Remove the previous </li>
					$active = '';

					if (isset($Shopp->Category) && !empty($parent->slug)
							&& preg_match('/(^|\/)'.$parent->path.'(\/|$)/',$Shopp->Category->uri)) {
						$active = ' active';
					}

					$subcategories = '<ul class="children'.$active.'">';
					$string .= $subcategories;
				}

				if (value_is_true($hierarchy) && $category->depth < $depth) {
					for ($i = $depth; $i > $category->depth; $i--) {
						if (substr($string,strlen($subcategories)*-1) == $subcategories) {
							// If the child menu is empty, remove the <ul> to avoid breaking standards
							$string = substr($string,0,strlen($subcategories)*-1).'</li>';
						} else $string .= '</ul></li>';
					}
				}

				$category_uri = empty($category->id)?$category->uri:$category->id;
				$link = SHOPP_PRETTYURLS?
					shoppurl("category/$category->uri"):
					shoppurl(array('s_cat'=>$category_uri));

				$total = '';
				if (value_is_true($products) && $category->total > 0) $total = ' <span>('.$category->total.')</span>';

				$current = '';
				if (isset($Shopp->Category) && $Shopp->Category->slug == $category->slug)
					$current = ' class="current"';

				$listing = '';
				if ($category->total > 0 || isset($category->smart) || $linkall)
					$listing = '<a href="'.$link.'"'.$current.'>'.$category->name.($linkcount?$total:'').'</a>'.(!$linkcount?$total:'');
				else $listing = $category->name;

				if (value_is_true($showall) ||
					$category->total > 0 ||
					isset($category->smart) ||
					$category->children)
					$string .= '<li'.$current.'>'.$listing.'</li>';

				$previous = &$category;
				$depth = $category->depth;
				$count++;
			}
			if (value_is_true($hierarchy) && $depth > 0)
				for ($i = $depth; $i > 0; $i--) {
					if (substr($string,strlen($subcategories)*-1) == $subcategories) {
						// If the child menu is empty, remove the <ul> to avoid breaking standards
						$string = substr($string,0,strlen($subcategories)*-1).'</li>';
					} else $string .= '</ul></li>';
				}
			if ($wraplist) $string .= '</ul>';
		}
		return $string;
	}

	function total ($result, $options, $obj) { return $obj->loaded?$obj->total:false; }

	function url ($result, $options, $obj) { return shoppurl(SHOPP_PRETTYURLS?'category/'.$obj->uri:array('s_cat'=>$obj->id)); }

}

?>