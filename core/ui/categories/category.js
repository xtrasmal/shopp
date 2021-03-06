var Pricelines = new Pricelines(),
	productOptions = new Array(),
	optionMenus = new Array(),
	detailsidx = 1,
	variationsidx = 1,
	optionsidx = 1,
	pricingidx = 1,
	pricelevelsidx = 1,
	fileUploader = false,
	changes = false,
	saving = false,
	flashUploader = false,
	template = true;

jQuery(document).ready(function () {
	var $=jqnc(),
		editslug = new SlugEditor(category,'category'),
		imageUploads = new ImageUploads($('#image-category-id').val(),'category');

	postboxes.add_postbox_toggles('shopp_page_shopp-categories');
	// close postboxes that should be closed
	$('.if-js-closed').removeClass('if-js-closed').addClass('closed');

	$('.postbox a.help').click(function () {
		$(this).colorbox({iframe:true,open:true,innerWidth:768,innerHeight:480,scrolling:false});
		return false;
	});

	updateWorkflow();
	$('#category').submit(function () {
		this.action = this.action.substr(0,this.action.indexOf("?"))+"?"+$.param(request);
		return true;
	});

	$('#templates, #details-template, #details-facetedmenu, #variations-template, #variations-pricing, #price-ranges, #facetedmenus-setting').hide();

	$('#spectemplates-setting').change(function () {
		if (this.checked) $('#templates, #details-template, #facetedmenus-setting').show();
		else $('#details-template, #facetedmenus-setting').hide();
		if (!$('#spectemplates-setting').attr('checked') && !$('#variations-setting').attr('checked'))
			$('#templates').hide();
	}).change();

	$('#faceted-setting').change(function () {
		if (this.checked) {
			$('#details-menu').removeClass('options').addClass('menu');
			$('#details-facetedmenu, #price-ranges').show();
		} else {
			$('#details-menu').removeClass('menu').addClass('options');
			$('#details-facetedmenu, #price-ranges').hide();
		}
	}).change();

	if (details) for (s in details) addDetail(details[s]);
	$('#addPriceLevel').click(function() { addPriceLevel(); });
	$('#addDetail').click(function() { addDetail(); });

	$('#variations-setting').bind('toggleui',function () {
		if (this.checked) $('#templates, #variations-template, #variations-pricing').show();
		else $('#variations-template, #variations-pricing').hide();
		if (!$('#spectemplates-setting').attr('checked') && !$('#variations-setting').attr('checked'))
			$('#templates').hide();
	}).click(function() {
		$(this).trigger('toggleui');
	}).trigger('toggleui');
	if (options) loadVariations( !(options.v) ? options : options.v, prices );
	$('#addVariationMenu').click(function() { addVariationOptionsMenu(); });

	$('#pricerange-facetedmenu').change(function () {
		if ($(this).val() == "custom") $('#pricerange-menu, #addPriceLevel').show();
		else $('#pricerange-menu, #addPriceLevel').hide();
	}).change();

	if (priceranges) for (key in priceranges) addPriceLevel(priceranges[key]);

	if (!category) $('#title').focus();

	function addPriceLevel (data) {
		var menus = $('#pricerange-menu');
		var id = pricelevelsidx++;
		var menu = new NestedMenu(id,menus,'priceranges','',data,false,
			{'axis':'y','scroll':false});
		$(menu.label).change(function (){ this.value = asMoney(this.value); }).change();
	}

	function addDetail (data) {
		var menus = $('#details-menu'),
			entries = $('#details-list'),
			addOptionButton = $('#addDetailOption'),
			id = detailsidx,

			menu = new NestedMenu(
				id,menus,
				'specs',
				NEW_DETAIL_DEFAULT,
				data,
				{target:entries,type:'list'}
		);

		menu.items = new Array();
		menu.addOption = function (data) {
		 	var option = new NestedMenuOption(menu.index,menu.itemsElement,menu.dataname,NEW_OPTION_DEFAULT,data,true);
			menu.items.push(option);
		};

		var facetedSetting = $('<li class="setting"></li>').appendTo(menu.itemsElement);
		var facetedMenu = $('<select name="specs['+menu.index+'][facetedmenu]"></select>').appendTo(facetedSetting);
		$('<option value="disabled">'+FACETED_DISABLED+'</option>').appendTo(facetedMenu);
		$('<option value="auto">'+FACETED_AUTO+'</option>').appendTo(facetedMenu);
		$('<option value="ranges">'+FACETED_RANGES+'</option>').appendTo(facetedMenu);
		$('<option value="custom">'+FACETED_CUSTOM+'</option>').appendTo(facetedMenu);

		if (data && data.facetedmenu) facetedMenu.val(data.facetedmenu);

		facetedMenu.change(function () {
			if ($(this).val() == "disabled" || $(this).val() == "auto")  {
				$(addOptionButton).hide();
				$(menu.itemsElement).find('li.option').hide();
			} else {
				$(addOptionButton).show();
				$(menu.itemsElement).find('li.option').show();
			}
		}).change();

		// Load up existing options
		if (data && data.options) {
			for (var i in data.options) menu.addOption(data.options[i]);
		}


		$(menu.itemsElement).sortable({'axis':'y','items':'li.option','scroll':false});

		menu.element.unbind('click',menu.click);
		menu.element.click(function () {
			menu.selected();
			$(addOptionButton).unbind('click').click(menu.addOption);
			$(facetedMenu).change();
		});

		detailsidx++;
	}

	function updateWorkflow () {
		$('#workflow').change(function () {
			setting = $(this).val();
			request.page = adminpage;
			request.id = category;
			if (!request.id) request.id = "new";
			if (setting == "new") {
				request.id = "new";
				request.next = setting;
			}
			if (setting == "close") delete request.id;

			// Find previous product
			if (setting == "previous") {
				$.each(worklist,function (i,entry) {
					if (entry.id != category) return;
					if (worklist[i-1]) request.next = worklist[i-1].id;
					else delete request.id;
				});
			}

			// Find next product
			if (setting == "next") {
				$.each(worklist,function (i,entry) {
					if (entry.id != category) return;
					if (worklist[i+1]) request.next = worklist[i+1].id;
					else delete request.id;
				});
			}

		}).change();
	}

});