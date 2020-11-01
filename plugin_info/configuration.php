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
		<legend>
			<i class="fa fa-list-alt"></i> {{Paramètres}}
		</legend>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{Auto-actualisation (cron)}}</label>
			<div class="col-lg-4">
				<select class="configKey form-control" data-l1key="autorefresh" >
					<option value="* * * * *">{{Toutes les minutes}}</option>
					<option value="*/5 * * * *">{{Toutes les 5 minutes}}</option>
					<option value="*/10 * * * *">{{Toutes les 10 minutes}}</option>
					<option value="*/15 * * * *">{{Toutes les 15 minutes}}</option>
					<option value="*/30 * * * *">{{Toutes les 30 minutes}}</option>
					<option value="*/45 * * * *">{{Toutes les 45 minutes}}</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-4 control-label">{{Créer un objet pour chaque nouveau client}}</label>
			<div class="col-sm-2">
				<input type="checkbox" class="configKey tooltips" data-l1key="createClients">
			</div>
		</div>
		<div class="form-group">
		  <label class="col-lg-4 control-label" >{{Pièce par défaut pour les nouveaux clients}}</label>
		  <div class="col-lg-3">
			<select id="sel_object" class="configKey form-control" data-l1key="defaultParentObject">
			  <option value="">{{Aucune}}</option>
			  <?php
				foreach (jeeObject::all() as $object) {
				  echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
				}
			  ?>
			</select>
		  </div>
		</div>
<?php
	$ignoredClients=config::byKey('ignoredClients','livebox',[],true);
	if(count($ignoredClients)) :
?>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Réinitialiser}}</label>
			<div class="col-lg-3">
				<a class="btn btn-default" id="bt_noMoreIgnore"><i class='fa fa-trash'></i> {{Ne plus ignorer les clients supprimés}}</a>
			</div>
		</div>
<?php
	endif;
?>
	</fieldset>
	<fieldset>
		<div class="form-group">
			<label class="col-sm-4 control-label">{{Utiliser Pages jaunes}}</label>
			<div class="col-sm-2">
				<input type="checkbox" class="configKey tooltips" data-l1key="pagesjaunes">
			</div>

		</div>
		<div class="form-group">
			<label class="col-sm-4 control-label">{{Nom par défaut pour les inconnus}}</label>
			<div class="col-sm-2">
				<input id="unknowndefault" class="configKey form-control" data-l1key="nominconnu" placeholder="{{Oups}}"/>
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
	<div class="tblfavorites">
	<table class="table table-bordered table-condensed" id="table_favorites">
	  <thead>
		<tr>
		  <th>{{Nom}}</th>
		  <th>{{Numéro de téléphone}}</th>
		  <th>{{Action}}</th>
		</tr>
	  </thead>
	  <tbody>

	  </tbody>
	</table>
	</div>
  </fieldset>
</form>
<style>
	div.tblfavorites {
		overflow-y:scroll;
		border:#000000 1px solid;
		min-height:15px;
		max-height:180px;
		width: 50%;
	}
</style>
<script>
$('#bt_noMoreIgnore').on('click', function () {
	$.ajax({// fonction permettant de faire de l'ajax
		type: "POST", // methode de transmission des données au fichier php
		url: "plugins/livebox/core/ajax/livebox.ajax.php", // url du fichier php
		data: {
			action: "noMoreIgnore",
			what: "clients"
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
		$('#div_alert').showAlert({message: '{{Action réussie}}', level: 'success'});
	  }
	});
});

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
	if (data.length > 1 ) data.sort((a, b) => a.callerName.localeCompare(b.callerName));
	var tr='';
	for(var i in data){
	  tr += '<tr class="favorite">';
	  tr += '<td>';
	  tr += '<input class="form-control favoriteAttr" data-l1key="callerName" value="'+data[i].callerName+'" />';
	  tr += '</td>';
	  tr += '<td>';
	  tr += '<input class="form-control favoriteAttr" data-l1key="phone" value="'+data[i].phone+'" />';
	  tr += '</td>';
	  tr += '<td>';
	  tr += '<a class="btn btn-default btn-xs bt_removeFavorite pull-right"><i class="fas fa-minus"></i></a>';
	  tr += '</td>';
	  tr += '</tr>';
	}
	$('#table_favorites tbody').empty().append(tr);
  }
});

function livebox_postSaveConfiguration(){
  var favorites = $('#table_favorites .favorite').getValues('.favoriteAttr');
  for( var i = favorites.length-1; i>=0;i--){
	 if ( favorites[i].phone == '' || favorites[i].callerName == '') favorites.splice(i, 1);
  }
  jeedom.config.save({
	configuration:{'favorites' : favorites},
	plugin : 'livebox',
	error: function (error) {
	  $('#div_alert').showAlert({message: error.message, level: 'danger'});
	},
	success: function () {

	}
  });
}

$('#bt_addFavorite').off('click').on('click',function(){
  var tr = '<tr class="favorite">';
  tr += '<td>';
  tr += '<input class="form-control favoriteAttr" data-l1key="callerName" />';
  tr += '</td>';
  tr += '<td>';
  tr += '<input class="form-control favoriteAttr" data-l1key="phone" />';
  tr += '</td>';
  tr += '</tr>';
  $('#table_favorites tbody').append(tr);
});
$('#table_favorites').off('click','.bt_removeFavorite').on('click','.bt_removeFavorite',function(){
  $(this).closest('tr').remove();
});
</script>
