 $('#bt_healthlivebox').on('click', function () {
	$('#md_modal').dialog({title: "{{Santé Livebox}}"});
	$('#md_modal').load('index.php?v=d&plugin=livebox&modal=health').dialog('open');
});

document.querySelector('.eqLogicAction[data-action=createCommunityPost]')?.addEventListener('click', function(event) {
	jeedom.plugin.createCommunityPost({
		type: eqType,
		error: function(error) {
			domUtils.hideLoading()
			jeedomUtils.showAlert({
			  message: error.message,
			  level: 'danger'
			})
		},
		success: function(data) {
			let element = document.createElement('a');
			element.setAttribute('href', data.url);
			element.setAttribute('target', '_blank');
			element.style.display = 'none';
			document.body.appendChild(element);
			element.click();
			document.body.removeChild(element);
		}
	});
	return;
});

$('#in_searchEqlogic2').off('keyup').keyup(function () {
  var search = $(this).value().toLowerCase();
  search = search.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
  if(search == ''){
    $('.eqLogicDisplayCard.second').show();
    $('.eqLogicThumbnailContainer.second').packery();
    return;
  }
  $('.eqLogicDisplayCard.second').hide();
  $('.eqLogicDisplayCard.second .name').each(function(){
    var text = $(this).text().toLowerCase();
    text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
    if(text.indexOf(search) >= 0){
      $(this).closest('.eqLogicDisplayCard.second').show();
    }
  });
  $('.eqLogicThumbnailContainer.second').packery();
});
$('#bt_resetEqlogicSearch2').on('click', function () {
  $('#in_searchEqlogic2').val('')
  $('#in_searchEqlogic2').keyup()
})

$('.eqLogicAttr[data-l1key=configuration][data-l2key=type]').on('change',function(){
	if ($(this).value() == 'box') {
		$('#div_cron').show();
		$('#div_goCarte').show();
		$('#div_protocolBox').show();
		$('#div_ipBox').show();
		$('#div_portBox').show();
		$('#div_adminBox').show();
		$('#div_passBox').show();
	} else {
		$('#div_cron').hide();
		$('#div_goCarte').hide();
		$('#div_protocolBox').hide();
		$('#div_ipBox').hide();
		$('#div_portBox').hide();
		$('#div_adminBox').hide();
		$('#div_passBox').hide();
	}
});

function printEqLogicHelper(label,name,_eqLogic){
	var trm = '<tr><td class="col-sm-3"><span style="font-size : 1em;">'+label+'</span></td><td><span class="label label-default" style="font-size : 1em;"> <span class="eqLogicAttr" data-l1key="configuration" data-l2key="'+name+'"></span></span></td></tr>';
	
	$('#table_infoseqlogic tbody').append(trm);
	$('#table_infoseqlogic tbody tr:last').setValues(_eqLogic, '.eqLogicAttr');
}

function printEqLogicHelperBox(label,name,_eqLogic){
	var trm = '<tr><td class="col-sm-3"><span style="font-size : 1em;">'+label+'</span></td><td><span class="label label-default" style="font-size : 1em;"> <a id=boxName href=index.php?v=d&p=livebox&m=livebox&id='+_eqLogic.configuration.boxId+'>livebox</a></span></td></tr>';
	$('#table_infoseqlogic tbody').append(trm);

	jeedom.eqLogic.byId({
		id: _eqLogic.configuration.boxId,
		success: function(boxResult) {
			//console.log('id:'+boxResult.id+' name:'+boxResult.name)
			jeedom.object.byId({
				id: boxResult.object_id,
				success: function(roomResult) {
					//console.log('id:'+roomResult.id+' name:'+roomResult.name)
					document.getElementById("boxName").textContent = '['+roomResult.name+'] '+boxResult.name
				}
			})
		}
	})
}

// fonction executée par jeedom lors de l'affichage des details d'un eqlogic
function printEqLogic(_eqLogic) {
	if (!isset(_eqLogic)) {
		var _eqLogic = {configuration: {}};
	}
	if (!isset(_eqLogic.configuration)) {
		_eqLogic.configuration = {};
	}
	$('#table_infoseqlogic tbody').empty();
	if (_eqLogic.configuration.type=="box") {
    printEqLogicHelper("{{Fabricant}}","manufacturer",_eqLogic);
    printEqLogicHelper("{{Type}}","productClass",_eqLogic);
    printEqLogicHelper("{{Modèle}}","modelName",_eqLogic);
    printEqLogicHelper("{{Numéro de série}}","serialNumber",_eqLogic);
    printEqLogicHelper("{{Version hardware}}","hardwareVersion",_eqLogic);
    printEqLogicHelper("{{Version software}}","softwareVersion",_eqLogic);
		printEqLogicHelper("{{Adresse MAC}}","BaseMAC",_eqLogic);
		$('#div_cron').show();
		$('#div_goCarte').show();
		$('#div_protocolBox').show();
		$('#div_ipBox').show();
		$('#div_portBox').show();
		$('#div_adminBox').show();
		$('#div_passBox').show();
	}
	if (_eqLogic.configuration.type=="cli") {
		printEqLogicHelperBox("{{Livebox}}","boxId",_eqLogic);
		printEqLogicHelper("{{Type}}","deviceType",_eqLogic);
		printEqLogicHelper("{{Adresse MAC}}","macAddress",_eqLogic);
		$('#div_cron').hide();
		$('#div_goCarte').hide();
		$('#div_protocolBox').hide();
		$('#div_ipBox').hide();
		$('#div_portBox').hide();
		$('#div_adminBox').hide();
		$('#div_passBox').hide();
	}
    printScheduling(_eqLogic);
}

function printScheduling(_eqLogic){
  $.ajax({
    type: 'POST',
    url: 'plugins/livebox/core/ajax/livebox.ajax.php',
    data: {
      action: 'getLinkCalendar',
      id: _eqLogic.id,
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error);
    },
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({message: data.result, level: 'danger'});
        return;
      }
      $('#div_schedule').empty();
      console.log(data);
      if(data.result.length == 0){
        $('#div_schedule').append("<center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez encore aucune programmation. Veuillez cliquer <a href='index.php?v=d&m=calendar&p=calendar'>ici</a> pour programmer votre Livebox à l'aide du plugin agenda}}</span></center>");
      }else{
        var html = '<legend>{{Liste des programmations du plugin Agenda liées à la Livebox}}</legend>';
        for (var i in data.result) {
          var color = init(data.result[i].cmd_param.color, '#2980b9');
          if(data.result[i].cmd_param.transparent == 1){
            color = 'transparent';
          }
          html += '<span class="label label-info cursor" style="font-size:1.2em;background-color : ' + color + ';color : ' + init(data.result[i].cmd_param.text_color, 'black') + '">';
          html += '<a href="index.php?v=d&m=calendar&p=calendar&id='+data.result[i].eqLogic_id+'&event_id='+data.result[i].id+'" style="color : ' + init(data.result[i].cmd_param.text_color, 'black') + '">'
          if (data.result[i].cmd_param.eventName != '') {
            html += data.result[i].cmd_param.icon + ' ' + data.result[i].cmd_param.eventName;
          } else {
            html += data.result[i].cmd_param.icon + ' ' + data.result[i].cmd_param.name;
          }
          html += '</a></span>';
          html += ' ' + data.result[i].startDate.substr(11,5) + ' à ' + data.result[i].endDate.substr(11,5)+'<br\><br\>';
        }
        $('#div_schedule').empty().append(html);
      }
    }
  });
}

function addCmdToTable(_cmd) {
   if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    if (init(_cmd.logicalId) == 'refresh') {
        return;
    }
    if (init(_cmd.type) == 'info') {
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '" >';
        if (init(_cmd.logicalId) == 'brut') {
			tr += '<input type="hiden" name="brutid" value="' + init(_cmd.id) + '">';
		}
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}"></td>';
		tr += '</td>';
		tr += '<td>';
        tr += '</td>';
		tr += '<td>';
        tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom : 5px;" />';
        tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
        //if (_cmd.subType == 'numeric' || _cmd.subType == 'binary') {
        tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/>{{Historiser}}</label>';
        //}
        //if (_cmd.subType == 'numeric') {
        //  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;margin-top:7px;">'
        //}
        tr += '</td>';
        //tr += '<td><i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'; 
        tr += '</td>';	
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
        }
        tr += '</td>';
		table_cmd = '#table_cmd';
		if ( $(table_cmd+'_'+_cmd.eqType ).length ) {
			table_cmd+= '_'+_cmd.eqType;
		}
        $(table_cmd+' tbody').append(tr);
        $(table_cmd+' tbody tr:last').setValues(_cmd, '.cmdAttr');
    }
    if (init(_cmd.type) == 'action') {
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}">';
		tr += '</td>';
        tr += '<td>';
		tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> Icone</a>';
		tr += '<span class="cmdAttr cmdAction" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
        tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
        tr += '</td>';
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'; 
        tr += '</td>';
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
        }
//		tr += '<td><i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
		tr += '</tr>';

		table_cmd = '#table_cmd';
		if ( $(table_cmd+'_'+_cmd.eqType ).length ) {
			table_cmd+= '_'+_cmd.eqType;
		}
        $(table_cmd+' tbody').append(tr);
        $(table_cmd+' tbody tr:last').setValues(_cmd, '.cmdAttr');
        var tr = $(table_cmd+' tbody tr:last');
        jeedom.eqLogic.buildSelectCmd({
            id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
            filter: {type: 'info'},
            error: function (error) {
                $('#div_alert').showAlert({message: error.message, level: 'danger'});
            },
            success: function (result) {
                tr.find('.cmdAttr[data-l1key=value]').append(result);
                tr.setValues(_cmd, '.cmdAttr');
            }
        });
    }
}

$('#bt_cronGenerator').on('click', function(){
  jeedom.getCronSelectModal({},function (result) {
    $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
  });
});

$('#bt_goCarte').on('click', function() {
    $('#md_modal').dialog({title: "{{Accèder à l'interface de la livebox}}"});
	window.open($('.eqLogicAttr[data-l2key=protocol]').value()+'://'+$('.eqLogicAttr[data-l2key=ip]').value()+':'+$('.eqLogicAttr[data-l2key=port]').value()+'/');
});

$('#bt_showPassword').on('click', function() {
        event.preventDefault();
        if($('.eqLogicAttr[data-l1key=configuration][data-l2key=password]').attr('type') == 'text'){
            $('.eqLogicAttr[data-l1key=configuration][data-l2key=password]').attr('type', 'password');
            $('#bt_showPassword').addClass('fa-eye-slash');
            $('#bt_showPassword').removeClass('fa-eye');
        }else if($('.eqLogicAttr[data-l1key=configuration][data-l2key=password]').attr('type') == 'password'){
            $('.eqLogicAttr[data-l1key=configuration][data-l2key=password]').attr('type', 'text');
            $('#bt_showPassword').removeClass('fa-eye-slash');
            $('#bt_showPassword').addClass('fa-eye');
        }
});

$('.eqLogicAction[data-action=discover]').on('click', function (e) {
	var what=e.currentTarget.dataset.action2 || null;
	$.ajax({// fonction permettant de faire de l'ajax
		type: "POST", // methode de transmission des données au fichier php
		url: "plugins/livebox/core/ajax/livebox.ajax.php", // url du fichier php
		data: {
			action: "syncLivebox",
			what: what
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) { // si l'appel a bien fonctionné
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			$('#div_alert').showAlert({message: '{{Synchronisation réussie}} : '+what, level: 'success'});
			location.reload();
	  }
	});
});

$('.eqLogicAction[data-action=delete]').on('click', function (e) {
	var what=e.currentTarget.dataset.action2;
	bootbox.confirm('{{Cette action supprimera les '+what+' désactivés (grisés)<br/>Ceux-ci seront ignorés lors des prochains scans<br/>Pour réinitialiser les ignorés, allez dans la configuration du plugin}}', function(result) {
		if (result) {
			$.ajax({// fonction permettant de faire de l'ajax
				type: "POST", // methode de transmission des données au fichier php
				url: "plugins/livebox/core/ajax/livebox.ajax.php", // url du fichier php
				data: {
					action: "deleteDisabledEQ",
					what: what
				},
				dataType: 'json',
				error: function (request, status, error) {
					handleAjaxError(request, status, error);
				},
				success: function (data) { // si l'appel a bien fonctionné
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: data.result, level: 'danger'});
					return;
				}
				$('#div_alert').showAlert({message: '{{Suppression réussie}} : '+what, level: 'success'});
				location.reload();
			  }
			});
		}
	});
});

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});