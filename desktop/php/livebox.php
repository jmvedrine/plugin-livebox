<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'livebox');
$eqLogics = eqLogic::byType('livebox');
?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
	  <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
	   <div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add"  >
				<i class="fas fa-plus-circle"></i>
				<br>
				<span >{{Ajouter}}</span>
			</div>
	  		<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf" >
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
	  </div>
	</div>
        <legend><i class="fas fa-table"></i>{{Mes Livebox}}
        </legend>
		<div class="eqLogicThumbnailContainer">
			<?php
			if (count($eqLogics) == 0) {
				echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez pas encore de livebox, cliquez sur Ajouter un équipement pour commencer}}</span></center>";
			} else {
                foreach ($eqLogics as $eqLogic) {
                    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                    echo "<center>";
                    echo '<img src="plugins/livebox/plugin_info/livebox_icon.png" height="105" width="95" />';
                    echo "</center>";
                    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                    echo '</div>';
                }
			}
			?>
		</div>
    </div>
</div>
    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <br />
  <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
  <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
  <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
  <ul class="nav nav-tabs" role="tablist">
   <li role="presentation"><a href="" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
   <li role="presentation" class="active"><a href="#eqlogictabin" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
   <li role="presentation"><a href="#cmdtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
 </ul>
 <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
  <div role="tabpanel" class="tab-pane active" id="eqlogictabin">
	<br/>
		<div class="row">
		<div class="col-sm-6">
        <form class="form-horizontal">
            <fieldset>
                <legend>
                    {{Général}}
			   </legend>
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{Nom de la livebox}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la livebox}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label" >{{Objet parent}}</label>
                    <div class="col-lg-3">
                        <select class="form-control eqLogicAttr" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (jeeObject::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>'."\n";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{Catégorie}}</label>
                    <div class="col-lg-8">
                        <?php
                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                            echo '<label class="checkbox-inline">'."\n";
                            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                            echo '</label>'."\n";
                        }
                        ?>

                    </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label" ></label>
					<div class="col-sm-10">
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>Activer</label>
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>Visible</label>
					</div>
					<a class="btn btn-default" id="bt_goCarte" title='{{Accéder à la carte}}'><i class="fas fa-cogs"></i></a>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{IP de la livebox}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{Compte de la livebox}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{Password de la livebox}}</label>
                    <div class="col-lg-3">
                        <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password"/>
                    </div>
                </div>
            </fieldset> 
        </form>
        </div>
        <div class="col-sm-6">
            <form class="form-horizontal">
                <fieldset>
                    <table id="table_infoseqlogic" class="table table-condensed" style="border-radius: 10px;">
                        <thead>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
				</fieldset>
			</form>
		</div>
  </div>
  </div>
  <div role="tabpanel" class="tab-pane" id="cmdtab">
<br />
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 100px;">#</th>
                    <th style="width: 300px;">{{Nom}}</th>
					<th style="width: 120px;">{{Icône-action}}</th>
                    <th style="width: 120px;">{{Sous-Type}}</th>
                    <th style="width: 120px;">{{Options}}</th>
                    <th style="width: 100px;">{{Actions}}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
 </div>
 </div></div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'livebox', 'js', 'livebox'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
