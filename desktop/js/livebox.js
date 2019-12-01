function addCmdToTable(_cmd) {
   if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
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
		tr += '<input type=hidden class="cmdAttr form-control input-sm" data-l1key="unite" value="">';
        tr += '</td>';
        tr += '<td>';
		if (_cmd.logicalId == 'state' || _cmd.logicalId == 'tvstatus' || _cmd.logicalId == 'voipstatusH323' || _cmd.logicalId == 'voipstatusSIP' || _cmd.logicalId == 'connectionstate' || _cmd.logicalId == 'wifistatus' || _cmd.logicalId == 'wifi2.4status' || _cmd.logicalId == 'wifi5status' || _cmd.logicalId == 'linkstate' || _cmd.logicalId == 'debitmontant' || _cmd.logicalId == 'debitdescendant' || _cmd.logicalId == 'margebruitmontant' || _cmd.logicalId == 'margebruitdescendant') {
			tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/> {{Historiser}}<br/></span>';
		}
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
        tr += '</td>';
//		tr += '<td><i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';		
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
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
        tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
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
        jeedom.eqLogic.builSelectCmd({
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

$('#bt_goCarte').on('click', function() {
    $('#md_modal').dialog({title: "{{Accèder à l'interface de la livebox}}"});
	window.open('http://'+$('.eqLogicAttr[data-l2key=ip]').value()+'/');
});

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});