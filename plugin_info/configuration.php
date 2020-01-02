<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}
?>

<form class="form-horizontal">
	<fieldset>
		<div class="form-group">
			<label class="col-sm-4 control-label">{{Utiliser Pages jaunes}}</label>
			<div class="col-sm-2">
				<input type="checkbox" class="configKey tooltips" data-l1key="pagesjaunes">
			</div>

		</div>
		<div class="form-group">
			<label class="col-sm-4 control-label">{{Durée min appel entrant (s)}}</label>
			<div class="col-sm-2">
				<input class="configKey form-control" data-l1key="minincallduration" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-4 control-label">{{Regroupement par numéro de téléphone dans la liste des appels}}</label>
			<div class="col-sm-2">
				<input type="checkbox" class="configKey tooltips" data-l1key="groupCallsByPhone">
			</div>
		</div>
	</fieldset>
</form>
<form class="form-horizontal">
  <fieldset>
	<legend>{{Favoris}}
	  <a class="btn btn-xs btn-success pull-right" id="bt_addFavorite"><i class="fas fa-plus"></i> {{Ajouter}}</a>
	</legend>
	<table class="table table-bordered table-condensed" id="table_favorites" style="width:50% !important;">
	  <thead>
		<tr>
		  <th style="display: none; witdh: auto;">{{Id}}</th>
		  <th>{{Nom}}</th>
		  <th>{{Numéro de téléphone}}</th>
		  <th style="display: none; witdh: auto;">{{Date}}</th>
		  <th style="display: none; witdh: auto;">{{Pages Jaunes}}</th>
		  <th style="display: none; witdh: auto;">{{Favori}}</th>
		  <th>{{Action}}</th>
		</tr>
	  </thead>
	  <tbody>

	  </tbody>
	</table>
  </fieldset>
</form>

<script>
jeedom.config.load({
  configuration: 'favorites',
  plugin : 'livebox',
  error: function (error) {
    $('#div_alert').showAlert({message: error.message, level: 'danger'});
  },
  success: function (data) {
    if(data === false){
      return;
    }
    var tr='';
    for(var i in data){
      tr += '<tr class="favorite">';
      tr += '<td style="display: none; witdh: auto;">';
      tr += '<input class="form-control favoriteAttr" data-l1key="id" value="'+data[i].id+'" disabled />';
      tr += '</td>';
      tr += '<td>';
      tr += '<input class="form-control favoriteAttr" data-l1key="callerName" value="'+data[i].callerName+'" />';
      tr += '</td>';
      tr += '<td>';
      tr += '<input class="form-control favoriteAttr" data-l1key="phone" value="'+data[i].phone+'" />';
      tr += '</td>';
      tr += '<td style="display: none; witdh: auto;">';
      tr += '<input class="form-control favoriteAttr" data-l1key="startDate" value="'+data[i].startDate+'" disabled />';
      tr += '</td>';
      tr += '<td style="display: none; witdh: auto;">';
      tr += '<input class="form-control favoriteAttr" data-l1key="isFetched" value="'+data[i].isFetched+'" disabled />';
      tr += '</td>';
      tr += '<td style="display: none; witdh: auto;">';
      tr += '<input class="form-control favoriteAttr" data-l1key="favorite" value="'+data[i].favorite+'" disabled />';
      tr += '</td>';
      tr += '<td>';
      tr += '<a class="btn btn-default btn-xs bt_removeFavorite pull-right"><i class="fas fa-minus"></i></a>';
    }
    $('#table_favorites tbody').empty().append(tr);
  }
});

function livebox_postSaveConfiguration(){
  var favorites = $('#table_favorites .favorite').getValues('.favoriteAttr');
  jeedom.config.save({
	configuration:{'favorites' : favorites},
	plugin : 'livebox',
	error: function (error) {
	  $('#div_alert').showAlert({message: error.message, level: 'danger'});
	},
	success: function () {

	}
  });
  $.ajax({
	type: "POST",
	url: "plugins/livebox/core/ajax/livebox.ajax.php",
	data: {
	  action: "saveFavorites",
	},
	dataType: 'json',
	global: false,
	error: function (request, status, error) {
	  handleAjaxError(request, status, error);
	},
	success: function (data) {
	  if (data.state != 'ok') {
		$('#div_alert').showAlert({message: data.result, level: 'danger'});
		return;
	  }
	}
  });
}

$('#bt_addFavorite').off('click').on('click',function(){
  var tr = '<tr class="favorite">';
  tr += '<td style="display: none; witdh: auto;">';
  tr += '<input class="form-control favoriteAttr" data-l1key="id" disabled/>';
  tr += '</td>';
  tr += '<td>';
  tr += '<input class="form-control favoriteAttr" data-l1key="callerName" />';
  tr += '</td>';
  tr += '<td>';
  tr += '<input class="form-control favoriteAttr" data-l1key="phone" />';
  tr += '</td>';
  tr += '<td style="display: none; witdh: auto;">';
  tr += '<input class="form-control favoriteAttr" data-l1key="startDate" />';
  tr += '</td>';
  tr += '<td style="display: none; witdh: auto;">';
  tr += '<input class="form-control favoriteAttr" data-l1key="isFetched" />';
  tr += '</td style="display: none; witdh: auto;">';
  tr += '<td style="display: none; witdh: auto;">';
  tr += '<input class="form-control favoriteAttr" data-l1key="favorite" />';
  tr += '</td>';
  tr += '</tr>';
  $('#table_favorites tbody').append(tr);
});
$('#table_favorites').off('click','.bt_removeFavorite').on('click','.bt_removeFavorite',function(){
  $(this).closest('tr').remove();
});
</script>
