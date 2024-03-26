<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'livebox');
$eqLogics = eqLogic::byType('livebox');

$has = ["box"=>false,"cli"=>false];

foreach ($eqLogics as $eqLogic) {
    if ($eqLogic->getConfiguration('type') == '') {
        $eqLogic->setConfiguration('type', 'box');
        $eqLogic->setConfiguration('autorefresh', '* * * * *');
        $eqLogic->save();
    }
    $type=$eqLogic->getConfiguration('type','');
    if($type) {
        $has[$type]=true;
    }
}
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
            <div class="cursor logoSecondary" id="bt_healthlivebox">
                <i class="fas fa-medkit"></i>
                <br />
                <span>{{Santé}}</span>
            </div>
    </div>
        <legend><i class="fas fa-table"></i>{{Mes Livebox}}
        </legend>
        <div class="panel">
            <div class="panel-body">
                <div class="eqLogicThumbnailContainer ">
            <?php
                    if($has['box']) {
                        foreach ($eqLogics as $eqLogic) {
                            if($eqLogic->getConfiguration('type','') != 'box') {
                                continue;
                            }
                            $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                            echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
                            echo '<img src="' . $eqLogic->getImage() . '"/>';
                            echo '<br>';
                            echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                            echo '</div>';
                        }
            } else {
                        echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez pas encore de Livebox, cliquez sur Ajouter un équipement pour commencer}}</span></center>";
                    }
                    ?>

                </div>
            </div>
        </div>
        <legend><i class="fas fa-table"></i> {{Mes Clients}} <span class="cursor eqLogicAction" style="color:#fcc127" data-action="discover" data-action2="clients" title="{{Scanner les clients}}"><i class="fas fa-bullseye"></i></span>&nbsp;<span class="cursor eqLogicAction" style="color:#fcc127" data-action="delete" data-action2="clients" title="{{Supprimer Clients non-actifs (et ignorer lors des prochaines sync)}}"><i class="fas fa-trash"></i></span></legend>
        <div class="input-group" style="margin-bottom:5px;">
            <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic2" />
            <div class="input-group-btn">
                <a id="bt_resetEqlogicSearch2" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
            </div>
        </div>
        <div class="panel">
            <div class="panel-body">
                <div class="eqLogicThumbnailContainer  second">
                    <?php
                    if($has['cli']) {
                foreach ($eqLogics as $eqLogic) {
                            if($eqLogic->getConfiguration('type','') != 'cli') {
                                continue;
                            }
                            $opacity = '';
                            if ($eqLogic->getIsEnable() != 1) {
                                $opacity = ' disableCard';
                            }

                            echo '<div class="eqLogicDisplayCard cursor  second '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
                            echo '<img src="' . $eqLogic->getImage() . '"/>';
                            echo '<br>';
                            echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                    echo '</div>';
                }
                    } else {
                        echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Scannez les clients pour les créer}}</span></center>";
            }
            ?>

                </div>
            </div>
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
   <?php
   try {
       $plugin = plugin::byId('calendar');
       if (is_object($plugin) && $plugin->isActive()) {
           ?>
           <li role="presentation"><a href="#scheduletab" aria-controls="profile" role="tab" data-toggle="tab"><i class="far fa-clock"></i> {{Programmation}}</a></li>
           <?php
       }
   } catch (Exception $e) {

   }
   ?>
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
                    <label class="col-lg-2 control-label">{{Nom de l'équipement}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la Livebox}}"/>
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

                </div>
                <div class="form-group" id="div_cron" style="display: none;">
                  <label class="col-sm-2 control-label">{{Auto-actualisation (cron)}}</label>
                    <div class="col-sm-3">
                      <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Auto-actualisation (cron)}}"/>
                    </div>
                    <div class="col-sm-1">
                      <i class="fas fa-question-circle cursor floatright" id="bt_cronGenerator"></i>
                    </div>
                </div>
                <div class="form-group" id="div_goCarte" style="display: none;">
                    <label class="col-lg-2 control-label" >{{Accéder à la Livebox}}</label>
                    <div class="col-lg-3">
                        <a class="btn btn-default" id="bt_goCarte" title='{{Accéder à la Livebox}}'><i class="fas fa-cogs"></i></a>
                    </div>
                </div>
                <div class="form-group" id="div_ipBox" style="display: none;">
                    <label class="col-lg-2 control-label">{{IP de la Livebox}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip"/>
                    </div>
                </div>
                <div class="form-group" id="div_adminBox" style="display: none;">
                    <label class="col-lg-2 control-label">{{Compte de la Livebox}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username"/>
                    </div>
                </div>
                <div class="form-group" id="div_passBox" style="display: none;">
                    <label class="col-lg-2 control-label">{{Password de la Livebox}}</label>
                    <div class="col-lg-3">
                        <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password"/>
                    </div>
                    <div class="col-lg-1">
                        <i class="fas fa-eye-slash" id="bt_showPassword"></i>
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
 <div role="tabpanel" class="tab-pane" id="scheduletab">
    <form class="form-horizontal">
        <fieldset>
            <br/>
            <div id="div_schedule"></div>
        </fieldset>
    </form>
    <br/>
    <div class="alert alert-info">{{Dans cet onglet vous pouvez voir s'il y a un planning dans le plugin agenda agissant sur votre équipement Livebox..<br>
        Exemple : planifier une plage d'activation du Wifi.}}
    </div>
 </div>
 </div></div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'livebox', 'js', 'livebox'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
