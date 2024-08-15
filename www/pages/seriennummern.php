<?php

/*
 * Copyright (c) 2022 OpenXE project
 */

use Xentral\Components\Database\Exception\QueryFailureException;
use Xentral\Modules\SystemNotification\Service\NotificationMessageData;
use Xentral\Modules\SystemNotification\Service\NotificationService;

class Seriennummern {

    function __construct($app, $intern = false) {
        $this->app = $app;
        if ($intern)
            return;

        $this->app->ActionHandlerInit($this);
        $this->app->ActionHandler("list", "seriennummern_artikel_list");
        $this->app->ActionHandler("nummern_list", "seriennummern_nummern_list");
        $this->app->ActionHandler("lieferscheine_list", "seriennummern_lieferscheine_list");                                
        $this->app->ActionHandler("enter", "seriennummern_enter"); 
        $this->app->ActionHandler("delete", "seriennummern_delete");
        $this->app->ActionHandler("remove", "seriennummern_remove");
        $this->app->ActionHandler("minidetail", "seriennummern_minidetail");
        $this->app->DefaultActionHandler("list");
        $this->app->ActionHandlerListen($app);
    }

    public function Install() {
        /* Fill out manually later */
    }

    static function TableSearch(&$app, $name, $erlaubtevars) {
        switch ($name) {
            case "seriennummern_list":
                $allowed['seriennummern_list'] = array('list');
//                $heading = array('Artikel-Nr.','Artikel', 'Seriennummer','Erfasst am','Eingelagert','Adresse','Lieferschein','Lieferdatum', 'Men&uuml;');
                $heading = array('Artikel-Nr.','Artikel', 'Seriennummer','Erfasst am','Eingelagert', 'Men&uuml;');
                $width = array('10%','20%','20%','10%','1%','1%'); // Fill out manually later

                // columns that are aligned right (numbers etc)
                // $alignright = array(4,5,6,7,8); 

//                $findcols = array('a.nummer', 'a.name_de', 's.seriennummer','s.datum','s.eingelagert','lh.adresse_name','lh.belegnr','lh.datum','s.id');
                $findcols = array('a.nummer', 'a.name_de', 's.seriennummer','s.datum','s.eingelagert','s.id');
                $searchsql = array('a.nummer', 'a.name_de', 's.seriennummer');

                $defaultorder = 1;
                $defaultorderdesc = 0;
                $aligncenter = array();
                $alignright = array();
                $numbercols = array();
                $sumcol = array();

        		$dropnbox = "'<img src=./themes/new/images/details_open.png class=details>' AS `open`, CONCAT('<input type=\"checkbox\" name=\"auswahl[]\" value=\"',s.id,'\" />') AS `auswahl`";

//                $moreinfo = true; // Allow drop down details
//                $moreinfoaction = "lieferschein"; // specify suffix for minidetail-URL to allow different minidetails
//                $menucol = 11; // Set id col for moredata/menu

                $menu = "<table cellpadding=0 cellspacing=0><tr><td nowrap>" . "<a href=\"#\" onclick=DeleteDialog(\"index.php?module=seriennummern&action=delete&id=%value%\");>" . "<img src=\"themes/{$app->Conf->WFconf['defaulttheme']}/images/delete.svg\" border=\"0\"></a>" . "</td></tr></table>";

                $lieferschein_link = array(
                    '<a href="index.php?module=lieferschein&action=edit&id=',
                    ['sql' => 'lh.lieferschein'],
                    '">',
                    ['sql' => 'lh.belegnr'],
                    '</a>',    
                );

/*                $sql_columns = "
                    s.id,
                    CONCAT('<a href=\"index.php?module=artikel&action=edit&id=',a.id,'\">',a.nummer,'</a>') as nummer,
                    a.name_de,
                    s.seriennummer,
                    ".$app->erp->FormatDateTime("s.datum").",
                    if(s.eingelagert,'Ja','Nein'),
                    lh.adresse_name,
                    ".$app->erp->ConcatSQL($lieferschein_link).",
                    ".$app->erp->FormatDate("lh.datum").",
                    s.id 
                ";*/

                $sql_columns = "
                    s.id,
                    CONCAT('<a href=\"index.php?module=artikel&action=edit&id=',a.id,'\">',a.nummer,'</a>') as nummer,
                    a.name_de,
                    s.seriennummer,
                    ".$app->erp->FormatDateTime("s.datum").",
                    if(s.eingelagert,'Ja','Nein'),                    
                    s.id 
                ";


                $sql_tables = "
                            seriennummern s 
                        INNER JOIN 
                            artikel a ON s.artikel = a.id
                        LEFT JOIN (
                            SELECT DISTINCT
                                slp.seriennummer,        
                                a.id adresse,
                                a.name adresse_name,
                                l.datum,
                                l.id lieferschein,
                                l.belegnr
                            FROM
                                seriennummern_lieferschein_position slp
                            INNER JOIN lieferschein_position lp ON
                                lp.id = slp.lieferschein_position
                            INNER JOIN lieferschein l ON
                                l.id = lp.lieferschein
                            INNER JOIN adresse a ON
                                a.id = l.adresse
                            ORDER BY
                                l.datum
                            DESC
                                ,
                                l.id
                            DESC
                        ) lh ON lh.seriennummer = s.id                     
                 ";

                $artikel_id = $app->User->GetParameter('seriennummern_artikel_id');

                $where = "(a.id = '".$artikel_id."' OR '".$artikel_id."' = '')";
                $count = "SELECT COUNT(DISTINCT s.id) FROM ".$sql_tables." WHERE ".$where;

                // Toggle filters
                $app->Tpl->Add('JQUERYREADY', "$('#verfuegbar').click( function() { fnFilterColumn1( 0 ); } );");
                $app->Tpl->Add('JQUERYREADY', "$('#ausgelagert').click( function() { fnFilterColumn2( 0 ); } );");
                $app->Tpl->Add('JQUERYREADY', "$('#versendet').click( function() { fnFilterColumn3( 0 ); } );");

                for ($r = 1;$r <= 3;$r++) {
                  $app->Tpl->Add('JAVASCRIPT', '
                                         function fnFilterColumn' . $r . ' ( i )
                                         {
                                         if(oMoreData' . $r . $name . '==1)
                                         oMoreData' . $r . $name . ' = 0;
                                         else
                                         oMoreData' . $r . $name . ' = 1;

                                         $(\'#' . $name . '\').dataTable().fnFilter( 
                                           \'\',
                                           i, 
                                           0,0
                                           );
                                         }
                                         ');
                }

                $more_data1 = $app->Secure->GetGET("more_data1");
                if ($more_data1 == 1) {
                   $where .= " AND s.eingelagert = 1";                 
                } else {
                }
                
                $more_data2 = $app->Secure->GetGET("more_data2");
                if ($more_data2 == 1) {
                   $where .= " AND s.eingelagert = 0";                 
                } else {
                }

                $more_data3 = $app->Secure->GetGET("more_data3");
                if ($more_data3 == 1) {
                   $where .= " AND lh.seriennummer IS NOT NULL";                 
                } else {
                }
            
                $groupby = "GROUP BY s.id";

                $sql = "SELECT SQL_CALC_FOUND_ROWS ".$sql_columns." FROM ".$sql_tables;
  
//                echo($sql." WHERE ".$where." ".$groupby);
//                echo($count); 

                break;
            case "seriennummern_artikel_list":
                $allowed['seriennummern_artikel_list'] = array('list');
                $heading = array('Artikel-Nr.', 'Artikel', 'Lagermenge', 'Nummern verf&uuml;gbar', 'Nummern ausgeliefert', 'Nummern gesamt', 'Men&uuml;','');
                $width = array('10%','90%','1%','1%','1%','1%','1%'); // Fill out manually later

                // columns that are aligned right (numbers etc)
                // $alignright = array(4,5,6,7,8); 

                $findcols = array('a.nummer', 'a.name_de' , 'null', 'null', 'null', 'null', 'null', 'null');
                $searchsql = array('a.name_de', 'a.nummer');

                $menucol = 1;
                $defaultorder = 1;
                $defaultorderdesc = 0;
                $aligncenter = array();
                $alignright = array(3,4,5,6,7);
                $numbercols = array();
                $sumcol = array();

        		$dropnbox = "'<img src=./themes/new/images/details_open.png class=details>' AS `open`, CONCAT('<input type=\"checkbox\" name=\"auswahl[]\" value=\"',a.id,'\" />') AS `auswahl`";

//                $moreinfo = true; // Allow drop down details
//                $moreinfoaction = "lieferschein"; // specify suffix for minidetail-URL to allow different minidetails
//                $menucol = 11; // Set id col for moredata/menu

                $menu_link = array(
                    '<a href="index.php?module=seriennummern&action=enter&artikel=',
                    ['sql' => 'a.id'],
                    '">',
                    '<img src="./themes/'.$app->Conf->WFconf['defaulttheme'].'/images/add.png" title="Neue Seriennummern erfassen" border="0">',
                    '</a>',    
                    '<a href="index.php?module=seriennummern&action=nummern_list&artikel=',
                    ['sql' => 'a.id'],
                    '">',
                    '<img src="./themes/'.$app->Conf->WFconf['defaulttheme'].'/images/lupe.svg" title="Seriennummern anzeigen" border="0">',
                    '</a>'    
                );

                $sql = "SELECT SQL_CALC_FOUND_ROWS 
                        a.id,
                        CONCAT('<a href=\"index.php?module=artikel&action=edit&id=',a.id,'\">',a.nummer,'</a>') as nummer,
                        a.name_de,
                        ".$app->erp->FormatMenge('auf_lager.anzahl').",
                        SUM(if(s.eingelagert = 1,1,0)),
                        SUM(if(s.eingelagert = 0,1,0)),
                        SUM(if(s.id IS NULL,0,1)),
                        ".$app->erp->ConcatSQL($menu_link).",
                        a.id
                    FROM 
                        artikel a
                    LEFT JOIN
                    (
                        SELECT
                            a.id,
                            a.nummer,
                            a.name_de name,
                            SUM(lpi.menge) anzahl
                        FROM
                            artikel a
                        INNER JOIN lager_platz_inhalt lpi ON
                            a.id = lpi.artikel
                        WHERE
                            a.seriennummern <> 'keine' AND a.seriennummern <> ''
                        GROUP BY
                            a.id
                    ) auf_lager ON auf_lager.id = a.id
                    LEFT JOIN
                        seriennummern s ON s.artikel = a.id                 
                ";

                $where = "a.seriennummern <> 'keine' AND a.seriennummern <> ''";
                $groupby = "GROUP BY a.id";
                $count = "SELECT count(DISTINCT a.id) FROM artikel a WHERE ".$where;
         
//                echo($sql." WHERE ".$where." ".$groupby);
//                echo($count); 

                break;
            case "seriennummern_lieferscheine_list":
                $allowed['seriennummern_artikel_list'] = array('list');
                $heading = array('Lieferschein', 'Vom', 'Adresse', 'Menge Artikel', 'Nummern zugeordnet', 'Nummern fehlen', 'Men&uuml;','');
                $width = array('10%','10%','20%','1%','1%','1%','1%','1%','1%'); // Fill out manually later

                // columns that are aligned right (numbers etc)
                $alignright = array(4,5,6,7); 

                $findcols = array('l.belegnr', 'l.datum', 'adr.name', 'null', 'null', 'null', 'null', 'null');
                $searchsql = array('l.belegnr');

                $defaultorder = 1;
                $defaultorderdesc = 0;
                $aligncenter = array();
                $numbercols = array();
                $sumcol = array();

        		$dropnbox = "'<img src=./themes/new/images/details_open.png class=details>' AS `open`, CONCAT('<input type=\"checkbox\" name=\"auswahl[]\" value=\"',l.id,'\" />') AS `auswahl`";

                //$menu = "<table cellpadding=0 cellspacing=0><tr><td nowrap>" . "<a href=\"index.php?module=seriennummern&action=edit&id=%value%\"><img src=\"./themes/{$app->Conf->WFconf['defaulttheme']}/images/edit.svg\" border=\"0\"></a>&nbsp;<a href=\"#\" onclick=DeleteDialog(\"index.php?module=seriennummern&action=delete&id=%value%\");>" . "<img src=\"themes/{$app->Conf->WFconf['defaulttheme']}/images/delete.svg\" border=\"0\"></a>" . "</td></tr></table>";

                $menu_link = array(
                    '<a href="index.php?module=seriennummern&action=enter&lieferschein=',
                    ['sql' => 'l.id'],
                    '&from=seriennummern">',
                    '<img src="./themes/'.$app->Conf->WFconf['defaulttheme'].'/images/edit.svg" title="Seriennummern erfassen" border="0">',
                    '</a>',    
                );

                $lieferschein_link = array(
                    '<a href="index.php?module=lieferschein&action=edit&id=',
                    ['sql' => 'l.id'],
                    '">',
                    ['sql' => 'l.belegnr'],
                    '</a>',    
                );

                $sql = "SELECT SQL_CALC_FOUND_ROWS 
                            l.id,
                            ".$app->erp->ConcatSQL($lieferschein_link).",
                            ".$app->erp->FormatDate("l.datum").",
                            adr.name,
                            ".$app->erp->FormatMengeFuerFormular("menge").",
                            SUM(if(slp.id IS NULL,0,1)),
                            ".$app->erp->FormatMengeFuerFormular("if(menge>SUM(if(slp.id IS NULL,0,1)),menge-SUM(if(slp.id IS NULL,0,1)),0) ").",
                            ".$app->erp->ConcatSQL($menu_link).",
                            l.id
                        FROM
                            lieferschein_position lp
                        LEFT JOIN seriennummern_lieferschein_position slp 
                            ON slp.lieferschein_position = lp.id
                        INNER JOIN lieferschein l ON
                            l.id = lp.lieferschein
                        INNER JOIN artikel a ON
                            a.id = lp.artikel
                        INNER JOIN adresse adr ON
                            adr.id = l.adresse
                ";

                $where = "(a.seriennummern <> 'keine')";

                // Toggle filters
                $app->Tpl->Add('JQUERYREADY', "$('#altelieferscheine').click( function() { fnFilterColumn1( 0 ); } );");

                for ($r = 1;$r <= 1;$r++) {
                  $app->Tpl->Add('JAVASCRIPT', '
                                         function fnFilterColumn' . $r . ' ( i )
                                         {
                                         if(oMoreData' . $r . $name . '==1)
                                         oMoreData' . $r . $name . ' = 0;
                                         else
                                         oMoreData' . $r . $name . ' = 1;

                                         $(\'#' . $name . '\').dataTable().fnFilter( 
                                           \'\',
                                           i, 
                                           0,0
                                           );
                                         }
                                         ');
                }

                $more_data1 = $app->Secure->GetGET("more_data1");
                if ($more_data1 == 1) {
                } else {
                   $where .= " AND (l.datum >= (SELECT DATE(MIN(datum)) FROM seriennummern WHERE artikel = a.id))";
                }


                $count = "SELECT COUNT(DISTINCT lp.lieferschein) FROM
                             lieferschein_position lp
                            LEFT JOIN seriennummern_lieferschein_position slp 
                                ON slp.lieferschein_position = lp.id
                            INNER JOIN lieferschein l ON
                                l.id = lp.lieferschein
                            INNER JOIN artikel a ON
                                a.id = lp.artikel 
                            "." WHERE ".$where;
    
                $groupby = "GROUP BY l.id";
                break;
           case "seriennummern_lieferschein_positionen":
                $allowed['seriennummern_artikel_list'] = array('list');
                $heading = array('','','Position', 'Artikel-Nr.', 'Artikel', 'Menge', 'Nummern zugeordnet', 'Nummern fehlen', 'Men&uuml;');
                $width = array('1%','1%','10%','10%','20%'); // Fill out manually later

                $lieferschein_id = $app->User->GetParameter('seriennummern_lieferschein_id');

                // columns that are aligned right (numbers etc)
                $alignright = array(6,7,8,9); 

                $findcols = array('lp.id','lp.id','lp.sort','a.nummer', 'a.name_de', 'null', 'null', 'null', 'null', 'null', 'null');
                $searchsql = array('l.belegnr');

                $moreinfo = true; // Allow drop down details
//                $moreinfoaction = "lieferschein"; // specify suffix for minidetail-URL to allow different minidetails
                $menucol = 1; // Set id col for moredata/menu

                $defaultorder = 1;
                $defaultorderdesc = 0;
                $aligncenter = array();
                $numbercols = array();
                $sumcol = array();

        		$dropnbox = "'<img src=./themes/new/images/details_open.png class=details>' AS `open`, CONCAT('<input type=\"checkbox\" name=\"auswahl[]\" value=\"',lp.id,'\" />') AS `auswahl`";

                $menu = "<a href=\"#\" onclick=DeleteDialog(\"index.php?module=seriennummern&action=remove&id=%value%\");>" . "<img src=\"themes/{$app->Conf->WFconf['defaulttheme']}/images/delete.svg\" border=\"0\"></a>";

                $lieferschein_link = array(
                    '<a href="index.php?module=lieferschein&action=edit&id=',
                    ['sql' => 'l.id'],
                    '">',
                    ['sql' => 'l.belegnr'],
                    '</a>',    
                );

                $sql = "SELECT SQL_CALC_FOUND_ROWS 
                            lp.id,  
                            $dropnbox,                          
                            lp.sort,
                            a.nummer,
                            a.name_de,
                            ".$app->erp->FormatMengeFuerFormular("menge").",
                            SUM(if(slp.id IS NULL,0,1)),
                            ".$app->erp->FormatMengeFuerFormular("menge-SUM(if(slp.id IS NULL,0,1))").",
                            lp.id
                        FROM
                            lieferschein_position lp
                        LEFT JOIN seriennummern_lieferschein_position slp 
                            ON slp.lieferschein_position = lp.id
                        INNER JOIN lieferschein l ON
                            l.id = lp.lieferschein
                        INNER JOIN artikel a ON
                            a.id = lp.artikel
                        INNER JOIN adresse adr ON
                            adr.id = l.adresse
                ";

                $where = "(a.seriennummern <> 'keine') AND (l.id = '".$lieferschein_id."')";
                $count = "SELECT COUNT(DISTINCT lp.lieferschein) FROM
                             lieferschein_position lp
                            LEFT JOIN seriennummern_lieferschein_position slp 
                                ON slp.lieferschein_position = lp.id
                            INNER JOIN lieferschein l ON
                                l.id = lp.lieferschein
                            INNER JOIN artikel a ON
                                a.id = lp.artikel 
                            "." WHERE ".$where;
    
                $groupby = "GROUP BY lp.id";

                $orderby = "ORDER BY lp.sort ASC";

//                echo($sql." WHERE ".$where." ".$groupby);

                break;
        }

        $erg = false;

        foreach ($erlaubtevars as $k => $v) {
            if (isset($$v)) {
                $erg[$v] = $$v;
            }
        }
        return $erg;
    }
    
    function seriennummern_menu() {
        $this->app->erp->MenuEintrag("index.php?module=seriennummern&action=list", "&Uuml;bersicht");
        $this->app->erp->MenuEintrag("index.php?module=seriennummern&action=nummern_list", "Seriennummern");
        $this->app->erp->MenuEintrag("index.php?module=seriennummern&action=lieferscheine_list", "Lieferscheine");
     //   $this->app->erp->MenuEintrag("index.php", "Zur&uuml;ck");    
    }
    
    function seriennummern_check_and_message($artikel_id) {
        $check_seriennummern = $this->seriennummern_check_serials($artikel_id);
               
        if (!empty($check_seriennummern)) {        
            $artikel_minus_id_links = array();
            $artikel_plus_id_links = array();                      
            foreach ($check_seriennummern as $artikel_id) {        
                if ($artikel_id['menge_nummern'] < $artikel_id['menge_auf_lager']) {                    
                    $artikel_minus_id_links[] = '<a href="index.php?module=seriennummern&action=enter&artikel='.$artikel_id['id'].'">'.$artikel_id['nummer'].'</a>';
                }
                else if ($artikel_id['menge_nummern'] > $artikel_id['menge_auf_lager']) {                    
                    $artikel_plus_id_links[] = '<a href="index.php?module=seriennummern&action=nummern_list&artikel='.$artikel_id['id'].'">'.$artikel_id['nummer'].'</a>';
                }
            }                
            if (!empty($artikel_minus_id_links)) {
                $this->app->YUI->Message('warning','Seriennummern fehlen f&uuml;r Artikel: '.implode(', ',$artikel_minus_id_links));                    
            }              
            if (!empty($artikel_plus_id_links)) {
                $this->app->YUI->Message('warning','Seriennummern Überschuss f&uuml;r Artikel: '.implode(', ',$artikel_plus_id_links));                    
            }              
        }              
    }
    
    function seriennummern_delivery_note_check_and_message($lieferschein_id) {
               
        $check_delivery_notes = $this->seriennummern_check_delivery_notes($lieferschein_id, group_lieferschein: true);
        if (!empty($check_delivery_notes)) {
            $lieferschein_minus_links = array();
            $lieferschein_plus_links = array();                      
            foreach ($check_delivery_notes as $check_delivery_note) {        
                if ($check_delivery_note['anzahl_nummern'] < $check_delivery_note['menge_lieferschein']) {                    
                    $lieferschein_minus_links[] = '<a href="index.php?module=seriennummern&action=enter&lieferschein='.$check_delivery_note['lieferschein'].'">'.$check_delivery_note['belegnr'].'</a>';
                }
                else if ($check_delivery_note['anzahl_nummern'] > $check_delivery_note['menge']) {                    
                    $lieferschein_plus_links[] = '<a href="index.php?module=seriennummern&action=nummern_list&lieferschein='.$check_delivery_note['lieferschein'].'">'.$check_delivery_note['belegnr'].'</a>';
                }
            }                
            if (!empty($lieferschein_minus_links)) {
                $this->app->YUI->Message('warning','Seriennummern fehlen f&uuml;r Lieferschein: '.implode(', ',$lieferschein_minus_links));                    
            }              
            if (!empty($lieferschein_plus_links)) {
                $this->app->YUI->Message('warning','Seriennummern Überschuss f&uuml;r Lieferschein: '.implode(', ',$lieferschein_plus_links));                    
            }            
        }         
    } 

    function seriennummern_nummern_list() {
    
        $this->seriennummern_menu();       
        
        // For transfer to tablesearch    
        $artikel_id = $this->app->Secure->GetGET('artikel');
        $this->app->User->SetParameter('seriennummern_artikel_id', $artikel_id);
        
        if (empty($artikel_id)) {
            $this->app->Tpl->Set('ARTIKEL_HIDDEN', "hidden");
        } else {
            $artikel = $this->app->DB->SelectRow("SELECT name_de, nummer FROM artikel WHERE id ='".$artikel_id."'");
            
            $check_seriennummern = $this->seriennummern_check_serials($artikel_id);                
            $check_seriennummern = $check_seriennummern[0];                          
              
            $this->app->Tpl->SetText('KURZUEBERSCHRIFT1','Anzeigen');                
            $this->app->Tpl->SetText('KURZUEBERSCHRIFT2',$artikel['name_de']." (Artikel ".$artikel['nummer'].")");
              
            $anzahl_fehlt = $check_seriennummern['menge_auf_lager']-$check_seriennummern['menge_nummern'];
        
            if ($anzahl_fehlt == 0) {
                $this->app->Tpl->addMessage('success', 'Seriennummern vollst&auml;ndig.');                 
            } 

            if ($anzahl_fehlt < 0) {
                $anzahl_fehlt = 0;
            }

            $letzte_seriennummer = (string) $this->app->DB->Select("SELECT seriennummer FROM seriennummern WHERE artikel = '".$artikel_id."' ORDER BY id DESC LIMIT 1");       
            $this->app->Tpl->Set('LETZTE', $letzte_seriennummer);

            $this->app->Tpl->Set('ANZAHL', $anzahl_fehlt);
            $this->app->Tpl->Set('ARTIKEL_ID', $artikel_id);

            $this->app->Tpl->Set('ARTIKELNUMMER', '<a href="index.php?module=artikel&action=edit&id='.$check_seriennummern['id'].'">'.$check_seriennummern['nummer'].'</a>');
            $this->app->Tpl->Set('ARTIKEL', $check_seriennummern['name']);
            $this->app->Tpl->Set('ANZLAGER', $check_seriennummern['menge_auf_lager']);        
            $this->app->Tpl->Set('ANZVORHANDEN', $check_seriennummern['menge_nummern']);
            $this->app->Tpl->Set('ANZFEHLT', $anzahl_fehlt);
        }
        
        $this->seriennummern_check_and_message($artikel_id);
        $this->seriennummern_delivery_note_check_and_message(null);

        $this->app->YUI->TableSearch('TAB1', 'seriennummern_list', "show", "", "", basename(__FILE__), __CLASS__);              

        $this->app->Tpl->Parse('PAGE', "seriennummern_nummern_list.tpl");
    }    

    function seriennummern_artikel_list() {
        $this->seriennummern_menu();
        $this->seriennummern_check_and_message(null);
        $this->seriennummern_delivery_note_check_and_message(null);

        $this->app->YUI->TableSearch('TAB1', 'seriennummern_artikel_list', "show", "", "", basename(__FILE__), __CLASS__);
               
        $this->app->Tpl->Parse('PAGE', "seriennummern_list.tpl");
    }    

    function seriennummern_lieferscheine_list() {
        $this->seriennummern_menu();
        $this->seriennummern_check_and_message(null);
        $this->seriennummern_delivery_note_check_and_message(null);

        $this->app->YUI->TableSearch('TAB1', 'seriennummern_lieferscheine_list', "show", "", "", basename(__FILE__), __CLASS__);
               
        $this->app->Tpl->Parse('PAGE', "seriennummern_lieferscheine_list.tpl");
    }   

    public function seriennummern_delete() {
        $id = (int) $this->app->Secure->GetGET('id');     

        if ($id) {
            if (!$this->app->DB->Select("SELECT id FROM `seriennummern_lieferschein_position` WHERE `seriennummer` = '{$id}'")) {
                $this->app->DB->Delete("DELETE FROM `seriennummern` WHERE `id` = '{$id}'");        
                $this->app->Tpl->addMessage('error', 'Der Eintrag wurde gel&ouml;scht');        
            } else {
                $this->app->Tpl->addMessage('error', 'Der Eintrag kann nicht gel&ouml;scht werden da eine Lieferscheinzuordnung existiert!');        
            }
            $this->seriennummern_nummern_list();
        }
        
    } 

    public function seriennummern_remove() {
        $id = (int) $this->app->Secure->GetGET('id');     

        if ($id) {
            if (!$this->app->DB->Delete("DELETE FROM `seriennummern_lieferschein_position` WHERE `lieferschein_position` = '{$id}'")) {
                $this->app->Tpl->addMessage('error', 'Der Eintrag wurde gel&ouml;scht');        
            }    
            $lieferschein = $this->app->DB->Select("SELECT lieferschein FROM lieferschein_position WHERE id = '".$id."'");
            $this->app->Location->execute("index.php?module=seriennummern&action=enter&lieferschein=$lieferschein&msg=$msg");
        }        
    } 

      
    function seriennummern_enter() {

        $this->app->erp->MenuEintrag("index.php?module=seriennummern&action=list", "Zur&uuml;ck zur &Uuml;bersicht");

        $task = "";

        $from = $this->app->Secure->GetGET('from');          

        $artikel_id = (int) $this->app->Secure->GetGET('artikel');
        if (!empty($artikel_id)) {
            $artikel = $this->app->DB->SelectRow("SELECT name_de, nummer FROM artikel WHERE id ='".$artikel_id."'");
            $this->app->Tpl->SetText('KURZUEBERSCHRIFT1','Erfassen');                
            $this->app->Tpl->SetText('KURZUEBERSCHRIFT2',$artikel['name_de']." (Artikel ".$artikel['nummer'].")");
            $this->app->Tpl->SetText('LEGEND',$artikel['name_de']." (Artikel ".$artikel['nummer'].")");
            $task = "artikel";
        }

        $lieferschein_id = (int) $this->app->Secure->GetGET('lieferschein');          
        if (!empty($lieferschein_id)) {
            $this->app->User->SetParameter('seriennummern_lieferschein_id', $lieferschein_id);
            $lieferschein = $this->app->DB->SelectRow("SELECT belegnr FROM lieferschein WHERE id ='".$lieferschein_id."'");
            $this->app->Tpl->SetText('KURZUEBERSCHRIFT1','Erfassen');                
            $this->app->Tpl->SetText('KURZUEBERSCHRIFT2','Lieferschein '.$lieferschein['belegnr']);
            $this->app->Tpl->SetText('LEGEND','Lieferschein <a href="index.php?module=lieferschein&action=edit&id='.$lieferschein_id.'">'.$lieferschein['belegnr'].'</a>', html: true);
            $task = "lieferschein";
        }

        $allowold = $this->app->Secure->GetPOST('allowold');        
        $submit = $this->app->Secure->GetPOST('submit');
        $seriennummern = array();
        $seriennummern_text = $this->app->Secure->GetPOST('seriennummern');
        $seriennummern = explode('\n',str_replace(['\r'],'',$seriennummern_text));           

        switch ($submit) {
            case 'assistent':
                $praefix = $this->app->Secure->GetPOST('praefix');
                $start = $this->app->Secure->GetPOST('start');
                $postfix = $this->app->Secure->GetPOST('postfix');
                $anzahl = (int) $this->app->Secure->GetPOST('anzahl');

                while ($anzahl) {
                    $seriennummern[] = $praefix.$start.$postfix;                          
                    $anzahl--;
                    $start++;
                }
            break;
            case 'hinzufuegen':
                $eingabescan = $this->app->Secure->GetPOST('eingabescan');
                $eingabe = $this->app->Secure->GetPOST('eingabe');                
                if (!empty($eingabe)) {
                   $seriennummern[] = $eingabe;                          
                }
                if (!empty($eingabescan)) {
                   $seriennummern[] = $eingabescan;                          
                }
            break;
            case 'einlagern':
                $seriennummern_not_written = array();
                $seriennummern_already_exist = array();
                $seriennummern_old_not_allowed = array();
                foreach ($seriennummern as $seriennummer) {  
                
                    $seriennummer = trim($seriennummer);
                              
                    if (empty($seriennummer)) {
                        continue;
                    }
                              
                    $sql = "SELECT id, eingelagert FROM seriennummern WHERE seriennummer = '".$this->app->DB->real_escape_string($seriennummer)."' AND artikel = '".$artikel_id."'";
                    $check_existing = $this->app->DB->SelectRow($sql);
            
                    if (empty($check_existing)) { // New serial
                        $sql = "INSERT INTO seriennummern (seriennummer, artikel, logdatei, eingelagert) VALUES ('".$this->app->DB->real_escape_string($seriennummer)."', '".$artikel_id."', CURRENT_TIMESTAMP, 1)";
                        try {                
                            $this->app->DB->Insert($sql);
                        } catch (mysqli_sql_exception $e) {
                            $error = true;
                            $seriennummern_not_written[] = $seriennummer;
                        }                     
                    } else {
                        if ($check_existing['eingelagert']) { // Old serial, already here
                            $seriennummern_already_exist[] = $seriennummer;
                        } else { // Old serial, returning
                            if ($allowold) {
                                $sql = "UPDATE seriennummern SET eingelagert = 1, logdatei = CURRENT_TIMESTAMP WHERE seriennummer = '".$this->app->DB->real_escape_string($seriennummer)."' AND artikel = '".$artikel_id."'";
                                $this->app->DB->Update($sql);
                            } else {
                                $seriennummern_old_not_allowed[] = $seriennummer;
                            }
                        }
                    }
                }                              
                if (!empty($seriennummern_already_exist)) {
                    $this->app->Tpl->addMessage('error', 'Seriennummern existieren bereits: '.implode(', ',$seriennummern_already_exist));          
                }
                if (!empty($seriennummern_old_not_allowed)) {
                    $this->app->Tpl->addMessage('error', 'Seriennummern bereits ausgeliefert: '.implode(', ',$seriennummern_old_not_allowed));          
                }
                if (!empty($seriennummern_not_written)) {
                    $this->app->Tpl->addMessage('error', 'Seriennummern konnten nicht gespeichert werden: '.implode(', ',$seriennummern_not_written));          
                }
                $seriennummern = array_merge($seriennummern_not_written, $seriennummern_already_exist, $seriennummern_old_not_allowed);
            break;
            case 'lieferscheinzuordnen':

                if (empty($lieferschein_id)) {
                    break;
                }

                $auswahl = $this->app->Secure->GetPOST('auswahl');                              
                if (!empty($auswahl)) {
                    if (count($auswahl) > 1) {
                        $this->app->Tpl->addMessage('error', 'Bitte eine oder keine Position anklicken');
                        break;
                    }
                    $lieferschein_position = $auswahl[0];
                    $sql_auswahl = " AND artikel IN (SELECT artikel FROM lieferschein_position WHERE id = '".$lieferschein_position."'";
                }

                $seriennummern_not_written = array();
                $seriennummern_dont_exist = array();
                $seriennummern_ambigious = array();             

                foreach ($seriennummern as $seriennummer) {  
                
                    $seriennummer = trim($seriennummer);
                              
                    if (empty($seriennummer)) {
                        continue;
                    }

                    $sql = "SELECT id, artikel FROM seriennummern WHERE eingelagert = 1 AND seriennummer = '".$this->app->DB->real_escape_string($seriennummer)."'".$sql_auswahl;

                    $check_existing = $this->app->DB->SelectArr($sql);
                    
                    if (empty($check_existing)) { 
                        $seriennummern_dont_exist[] = $seriennummer;                
                    } else if (count($check_existing) > 1) {
                        $seriennummern_ambigious[] = $seriennummer;
                    } else {

                        $check_lieferschein = $this->seriennummern_check_delivery_notes($lieferschein_id, ignore_date: true);        
                        $written = false;

                        foreach ($check_lieferschein as $position) {
                            $menge_offen = $position['menge_lieferschein']-$position['menge_nummern'];

                            if ($menge_offen > 0) {  
                                if (empty($lieferschein_position)) {
                                    $lieferschein_position = $position['lieferschein_position'];
                                }                                 
                                if ($lieferschein_position == $position['lieferschein_position']) {
                                    $sql = "INSERT INTO seriennummern_lieferschein_position (lieferschein_position, seriennummer) VALUES ('".$lieferschein_position."','".$check_existing[0]['id']."') ";

                                    $this->app->DB->Insert($sql);
                                    $written = true;
                                }
                            }
                        }

                        if (!$written) {
                            $seriennummern_not_written[] = $seriennummer;
                        }

                    }

                }                              
                if (!empty($seriennummern_dont_exist)) {
                    $this->app->Tpl->addMessage('error', 'Seriennummern existieren nicht: '.implode(', ',$seriennummern_dont_exist));          
                }
                if (!empty($seriennummern_ambigious)) {
                    $this->app->Tpl->addMessage('error', 'Seriennummern nicht eindeutig: '.implode(', ',$seriennummern_ambigious));          
                }
                $seriennummern = array_merge($seriennummern_not_written, $seriennummern_ambigious, $seriennummern_dont_exist);

            break;
        }       
        $seriennummern = array_unique($seriennummern);                

        $complete = false;

        switch ($task) {
            case 'artikel':
                $this->app->Tpl->Set('LIEFERSCHEIN_HIDDEN', "hidden=\"true\"");
                $check_seriennummern = $this->seriennummern_check_serials($artikel_id);                
                $check_seriennummern = $check_seriennummern[0];
                      
                $anzahl_fehlt = $check_seriennummern['menge_auf_lager']-$check_seriennummern['menge_nummern'];
                
                if ($anzahl_fehlt == 0) {
                    $this->app->Tpl->addMessage('success', 'Seriennummern vollst&auml;ndig.');                 
                } 

                if ($anzahl_fehlt < 0) {
                    $anzahl_fehlt = 0;
                }

                $letzte_seriennummer = (string) $this->app->DB->Select("SELECT seriennummer FROM seriennummern WHERE artikel = '".$artikel_id."' ORDER BY id DESC LIMIT 1");       
                $regex_result = array(preg_match('/(.*?)(\d+)(?!.*\d)(.*)/', $letzte_seriennummer, $matches));
                $this->app->Tpl->Set('LETZTE', $letzte_seriennummer);
                $this->app->Tpl->Set('PRAEFIX', $matches[1]);
                $this->app->Tpl->Set('START', $matches[2]+1);
                $this->app->Tpl->Set('POSTFIX', $matches[3]);

                $this->app->Tpl->Set('ANZAHL', $anzahl_fehlt);

                $this->app->Tpl->Set('ARTIKELNUMMER', '<a href="index.php?module=artikel&action=edit&id='.$check_seriennummern['id'].'">'.$check_seriennummern['nummer'].'</a>');

                $this->app->Tpl->Set('ARTIKEL', $check_seriennummern['name']);
                $this->app->Tpl->Set('ANZLAGER', $check_seriennummern['menge_auf_lager']);        
                $this->app->Tpl->Set('ANZVORHANDEN', $check_seriennummern['menge_nummern']);
                $this->app->Tpl->Set('ANZFEHLT', $anzahl_fehlt);
                $this->app->Tpl->Set('SERIENNUMMERN', implode("\n",$seriennummern));    

                $artikel_id = $this->app->Secure->GetGET('artikel');
                $this->app->User->SetParameter('seriennummern_artikel_id', $artikel_id);
                $this->app->YUI->TableSearch('POSITIONEN', 'seriennummern_list', "show", "", "", basename(__FILE__), __CLASS__);                                        
                                                       
            break;
            case 'lieferschein':

                switch ($from) {
                    case 'lieferschein':
                        $this->app->erp->MenuEintrag("index.php?module=lieferschein&action=edit&id=".$lieferschein_id, "Zur&uuml;ck");
                    break;
                    case 'seriennummern':
                        $this->app->erp->MenuEintrag("index.php?module=seriennummern&action=lieferscheine_list", "Zur&uuml;ck");
                    break;
                }

                $this->app->Tpl->Set('ARTIKEL_HIDDEN', "hidden");
                $this->app->Tpl->Set('LIEFERSCHEINNUMMER', '<a href="index.php?module=lieferschein&action=edit&id='.$lieferschein_id.'">'.$lieferschein['belegnr'].'</a>');

                $check_lieferschein = $this->seriennummern_check_delivery_notes($lieferschein_id, ignore_date: true);

                if (empty($check_lieferschein)) {
                    $this->app->Tpl->AddMessage('success', 'Seriennummern vollst&auml;ndig.');
                    $check_lieferschein = $this->seriennummern_check_delivery_notes($lieferschein_id, ignore_date: true, only_missing: false);                 
                    $this->app->Tpl->Set('EINGABE_HIDDEN', 'hidden');
                }

                $menge_lieferschein = array_sum(array_column($check_lieferschein,'menge_lieferschein'));
                $menge_nummern = array_sum(array_column($check_lieferschein,'menge_nummern'));
                $anzahl_fehlt = $menge_lieferschein-$menge_nummern;               

                if ($anzahl_fehlt < 0) {
                    $anzahl_fehlt = 0;
                }

                $this->app->Tpl->Set('ANZLIEFERSCHEIN', $menge_lieferschein);        
                $this->app->Tpl->Set('ANZVORHANDEN', $menge_nummern);
                $this->app->Tpl->Set('ANZFEHLT', $anzahl_fehlt);

                $artikel_lieferschein = $this->app->DB->SelectArr("SELECT artikel FROM lieferschein_position WHERE lieferschein = '".$lieferschein_id."'");

                $sql = "
                    SELECT
                        DISTINCT s.seriennummer
                    FROM
                        seriennummern s
                    LEFT JOIN
                        seriennummern_lieferschein_position slp ON slp.seriennummer = s.id
                    WHERE
                        s.artikel = '".$check_lieferschein[0]['artikel']."'
                        AND s.eingelagert = 1                        
                        AND slp.id IS NULL
                    ORDER BY s.id ASC
                    LIMIT 1
                ";       

                $letzte_seriennummer = (string) $this->app->DB->Select($sql);

                $regex_result = array(preg_match('/(.*?)(\d+)(?!.*\d)(.*)/', $letzte_seriennummer, $matches));
                $this->app->Tpl->Set('LETZTE', $letzte_seriennummer);
                $this->app->Tpl->Set('PRAEFIX', $matches[1]);
                $this->app->Tpl->Set('START', $matches[2]);
                $this->app->Tpl->Set('POSTFIX', $matches[3]);

                $fehlt = $check_lieferschein[0]['menge_lieferschein']-$check_lieferschein[0]['menge_nummern'];
                if ($fehlt < 0) {
                    $fehlt = 0;
                }

                $this->app->Tpl->Set('ANZAHL', $fehlt);

                $this->app->YUI->TableSearch('POSITIONEN', 'seriennummern_lieferschein_positionen', "show", "", "", basename(__FILE__), __CLASS__);              
                $this->app->YUI->AutoComplete("eingabe", "seriennummerverfuegbar",0,"&lieferschein=$lieferschein_id");   

                $this->app->Tpl->Set('SERIENNUMMERN', implode("\n",$seriennummern));                              

            break;
            default:
                exit();
            break;
        }

        $this->app->Tpl->Parse('PAGE', "seriennummern_enter.tpl");        
                
    }
   
    /*
    * Check if all serial numbers are given
    * Return array of article ids
    */
    public function seriennummern_check_serials($artikel_id = null) : array {
        $sql = "
            SELECT
                auf_lager.id,
                nummer,
                name,                
                ".$this->app->erp->FormatMenge('auf_lager.anzahl')." menge_auf_lager,
                COALESCE(nummern_verfuegbar.anzahl,0) menge_nummern
            FROM
                (
                SELECT
                    a.id,
                    a.nummer,
                    a.name_de name,
                    SUM(lpi.menge) anzahl
                FROM
                    artikel a
                INNER JOIN lager_platz_inhalt lpi ON
                    a.id = lpi.artikel
                WHERE
                    a.seriennummern <> 'keine' AND a.seriennummern <> '' AND (a.id = '".$artikel_id."' OR '".$artikel_id."' = '')
                GROUP BY
                    a.id
            ) auf_lager
            LEFT JOIN(
                SELECT
                    artikel,
                    SUM(if(eingelagert,1,0)) anzahl
                FROM
                    seriennummern
                GROUP BY
                    artikel
            ) nummern_verfuegbar
            ON
                auf_lager.id = nummern_verfuegbar.artikel
            GROUP BY
                auf_lager.id
        ";
        
        $result = $this->app->DB->SelectArr($sql);        
        return(empty($result)?array():$result);
    }

    /*
    * Check if all delivery notes have serials
    * Return array of delivery note positions and head information
    */
    public function seriennummern_check_delivery_notes($lieferschein_id = null, $ignore_date = false, $only_missing = true, $group_lieferschein = false) : array {

        if ($group_lieferschein) {
            $sql_lp = "''";
            $sql_lp_group = "";
        } else {
            $sql_lp = "lp.id";
            $sql_lp_group = ", lp.id";
        }

        $sql = "
                SELECT SQL_CALC_FOUND_ROWS
                    l.id lieferschein,
                    l.belegnr belegnr,
                    $sql_lp lieferschein_position,
                    a.id artikel,
                    a.nummer artikel_nummer,
                    a.name_de,
                    ".$this->app->erp->FormatMenge('SUM(menge)')." menge_lieferschein,
                    SUM(COALESCE(menge_nummern,0)) menge_nummern
                FROM
                    lieferschein_position lp                
                INNER JOIN lieferschein l ON
                    l.id = lp.lieferschein
                INNER JOIN artikel a ON
                    a.id = lp.artikel
                LEFT JOIN (
                    SELECT 
                        lieferschein_position,
                        COUNT(*) menge_nummern
                    FROM
                        seriennummern_lieferschein_position slp
                    GROUP BY
                        slp.lieferschein_position
                ) slp ON slp.lieferschein_position = lp.id
                WHERE
                    (a.seriennummern <> 'keine') 
                    AND (
                        l.datum >=(
                            SELECT
                                DATE(MIN(datum))
                            FROM
                                seriennummern
                            WHERE
                                artikel = a.id
                        ) OR ('".$ignore_date."' <> '')
                    ) 
                    AND (l.id = '".$lieferschein_id."' OR '".$lieferschein_id."' = '')
                    AND l.belegnr <> ''
                GROUP BY
                    l.id
                    $sql_lp_group
        ";

        if ($only_missing) {
            $sql .= " HAVING menge_lieferschein <> menge_nummern";
        }

        $result = $this->app->DB->SelectArr($sql);        
        return(empty($result)?array():$result);
    }
    
    protected function seriennummern_create_notification_artikel($artikel_id, $action, $title = 'Seriennummern', $message = 'Meldung', $button = 'Ok')
    {      
        // Notification erstellen
        $notification_message = new NotificationMessageData('default', $title);
        $artikel = $this->app->DB->SelectRow("SELECT name_de, nummer FROM artikel WHERE id = '".$artikel_id."' LIMIT 1");
        $notification_message->setMessage($message.' Artikel ('.$artikel['nummer'].') '.$artikel['name']);
        $notification_message->addTags(['seriennummern']);
        $notification_message->setPriority(true);

        $messageButtons = [
            [
                'text' => $button,
                'link' => sprintf('index.php?module=seriennummern&action='.$action.'&artikel=%s', $artikel_id),
            ]
        ];
        $notification_message->setOption('buttons', $messageButtons);

        /** @var NotificationService $notification */
        $notification = $this->app->Container->get('NotificationService');
        $notification->createFromData($this->app->User->GetID(), $notification_message);
    }

    protected function seriennummern_create_notification_lieferschein($lieferschein_id, $action, $title = 'Seriennummern', $message = 'Meldung', $button = 'Ok')
    {      
        // Notification erstellen
        $notification_message = new NotificationMessageData('default', $title);
        $lieferschein = $this->app->DB->SelectRow("SELECT belegnr FROM lieferschein WHERE id = '".$lieferschein_id."' LIMIT 1");
        $notification_message->setMessage($message.' Lieferschein '.$lieferschein['belegnr']);
        $notification_message->addTags(['seriennummern']);
        $notification_message->setPriority(true);

        $messageButtons = [
            [
                'text' => $button,
                'link' => sprintf('index.php?module=seriennummern&action='.$action.'&lieferschein=%s', $lieferschein_id),
            ]
        ];
        $notification_message->setOption('buttons', $messageButtons);

        /** @var NotificationService $notification */
        $notification = $this->app->Container->get('NotificationService');
        $notification->createFromData($this->app->User->GetID(), $notification_message);
    }
    
    /*
    * Check if new numbers need to be entered, if yes, create notification
    */
    public function seriennummern_check_and_message_stock_added(int $artikel_id) {
        $check_seriennummern = $this->seriennummern_check_serials($artikel_id);
        if ($check_seriennummern[0]['menge_nummern'] < $check_seriennummern[0]['menge_auf_lager']) {
            $this->seriennummern_create_notification_artikel($artikel_id, 'enter', 'Seriennummern','Bitte Seriennummern f&uuml;r Einlagerung erfassen','Zur Eingabe');
        }          
    }    

    /*
    * Check if numbers need to be entered after stock removal, if yes, create notification or message
    */
    public function seriennummern_check_and_message_delivery_note(int $lieferschein_id, bool $notification) {
        $check_delivery_notes = $this->seriennummern_check_delivery_notes($lieferschein_id);
        if (!empty($check_delivery_notes)) {
            if ($notificiation) {
                $this->seriennummern_create_notification_lieferschein($lieferschein_id, 'enter', 'Seriennummern','Bitte Seriennummern f&uuml;r Lieferschein erfassen','Zur Eingabe');
            } else {
                $this->app->Tpl->Add('MESSAGE',"<div class=\"warning\">Bitte Seriennummern erfassen <input type=\"button\" value=\"Jetzt erfassen\" onclick=\"window.location.href='index.php?module=seriennummern&action=enter&from=lieferschein&lieferschein=$lieferschein_id'\"></div>");
            }
        }          
    }

    public function seriennummern_minidetail($parsetarget='',$menu=true) {
        $id = $this->app->Secure->GetGET('id');

        if($parsetarget=='')
        {
            $tmp = new EasyTable($this->app);
            $tmp->Query("SELECT s.seriennummer FROM seriennummern s INNER JOIN seriennummern_lieferschein_position slp ON slp.seriennummer = s.id WHERE slp.lieferschein_position ='$id' ",0,"");
            $tmp->DisplayNew('TAB1',"Seriennummern","noAction");

            $this->app->Tpl->Output('emptytab.tpl');
            $this->app->ExitXentral();
        }   
    }
}
