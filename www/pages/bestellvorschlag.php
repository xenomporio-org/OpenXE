<?php

/*
 * Copyright (c) 2022 OpenXE project
 */

use Xentral\Components\Database\Exception\QueryFailureException;

class Bestellvorschlag {

    function __construct($app, $intern = false) {
        $this->app = $app;
        if ($intern)
            return;

        $this->app->ActionHandlerInit($this);
        $this->app->ActionHandler("list", "bestellvorschlag_list");        
        $this->app->ActionHandler("create", "bestellvorschlag_edit"); // This automatically adds a "New" button
        $this->app->ActionHandler("edit", "bestellvorschlag_edit");
        $this->app->ActionHandler("delete", "bestellvorschlag_delete");
        $this->app->DefaultActionHandler("list");
        $this->app->ActionHandlerListen($app);
    }

    public function Install() {
        /* Fill out manually later */
    }

    static function TableSearch(&$app, $name, $erlaubtevars) {
        switch ($name) {
            case "bestellvorschlag_list":
                $allowed['bestellvorschlag_list'] = array('list');
                $heading = array('',  '',  'Nr.', 'Artikel','Lieferant','Mindestlager','Lager','Bestellt','Vorschlag','Eingabe','');
                $width =   array('1%','1%','1%',  '20%',     '10%',       '1%',        '1%',     '1%',     '1%',     '5%',      '1%');

                // columns that are aligned right (numbers etc)
                // $alignright = array(4,5,6,7,8); 

                $findcols = array('a.id','a.id','a.nummer','a.name_de','l.name','mindestlager','lager','bestellt','vorschlag');
                $searchsql = array('a.name_de');

                $defaultorder = 1;
                $defaultorderdesc = 0;
                $numbercols = array(6,7,8,9);
//                $sumcol = array(6);
                $alignright = array(6,7,8,9);

        		$dropnbox = "'<img src=./themes/new/images/details_open.png class=details>' AS `open`, CONCAT('<input type=\"checkbox\" name=\"auswahl[]\" value=\"',a.id,'\" />') AS `auswahl`";

//                $menu = "<table cellpadding=0 cellspacing=0><tr><td nowrap>" . "<a href=\"index.php?module=bestellvorschlag&action=edit&id=%value%\"><img src=\"./themes/{$app->Conf->WFconf['defaulttheme']}/images/edit.svg\" border=\"0\"></a>&nbsp;<a href=\"#\" onclick=DeleteDialog(\"index.php?module=bestellvorschlag&action=delete&id=%value%\");>" . "<img src=\"themes/{$app->Conf->WFconf['defaulttheme']}/images/delete.svg\" border=\"0\"></a>" . "</td></tr></table>";

                $input_for_menge = "CONCAT(
                        '<input type = \"number\" min=\"0\"',
                        ' name=\"menge_',
                        a.id,
                        '\" value=\"',
                        (SELECT mengen.vorschlag),
                        '\" style=\"text-align:right\">',
                        '</input>'
                    )";

		$sql_artikel_mengen = "
			SELECT
			    a.id,
			    (
			        SELECT
			            COALESCE(SUM(menge),0)
			        FROM
			            lager_platz_inhalt lpi
			        INNER JOIN lager_platz lp ON
			            lp.id = lpi.lager_platz
			        WHERE
			            lpi.artikel = a.id AND lp.sperrlager = 0
				) AS lager_ber,
			    (
			    	SELECT 
			        	COALESCE(SUM(menge-geliefert))    
			        FROM
			        	bestellung_position bp INNER JOIN bestellung b ON bp.bestellung = b.id
			        WHERE bp.artikel = a.id AND b.status IN ('versendet','freigegeben')
			    ) AS bestellt_ber,
			    a.mindestlager - (SELECT lager_ber) - COALESCE((SELECT bestellt_ber),0) as vorschlag_ber,
			    FORMAT (a.mindestlager,0,'de_DE') as mindestlager,
			    FORMAT((SELECT lager_ber),0,'de_DE') as lager, 
			    FORMAT(COALESCE((SELECT bestellt_ber),0),0,'de_DE') as bestellt,
			    if ((SELECT vorschlag_ber) > 0,FORMAT((SELECT vorschlag_ber),'0','de_DE'),0) as vorschlag    
			FROM
			    artikel a
			              ";

                $sql = "SELECT SQL_CALC_FOUND_ROWS 
                    a.id, 
                    $dropnbox, 
                    a.nummer, 
                    a.name_de, 
                    l.name,
		    mengen.mindestlager,
		    mengen.lager,
	            mengen.bestellt,
		    mengen.vorschlag,"
		    .$input_for_menge
                    ."FROM 
			artikel a 
		    INNER JOIN 
			adresse l ON l.id = a.adresse 
		    INNER JOIN 
			(SELECT * FROM ($sql_artikel_mengen) mengen_inner WHERE mengen_inner.vorschlag > 0) as mengen ON mengen.id = a.id";

                $where = "a.adresse != '' AND a.geloescht != 1 AND a.inaktiv != 1";
                $count = "SELECT count(DISTINCT a.id) FROM artikel a WHERE $where";
//                $groupby = "";

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
    
    function bestellvorschlag_list() {

        $this->app->erp->MenuEintrag("index.php?module=bestellvorschlag&action=list", "&Uuml;bersicht");
        $this->app->erp->MenuEintrag("index.php?module=bestellvorschlag&action=create", "Neu anlegen");

        $this->app->erp->MenuEintrag("index.php", "Zur&uuml;ck");

        $this->app->YUI->TableSearch('TAB1', 'bestellvorschlag_list', "show", "", "", basename(__FILE__), __CLASS__);
        $this->app->Tpl->Parse('PAGE', "bestellvorschlag_list.tpl");
    }    

    public function bestellvorschlag_delete() {
        $id = (int) $this->app->Secure->GetGET('id');
        
        $this->app->DB->Delete("DELETE FROM `bestellvorschlag` WHERE `id` = '{$id}'");        
        $this->app->Tpl->Set('MESSAGE', "<div class=\"error\">Der Eintrag wurde gel&ouml;scht.</div>");        

        $this->bestellvorschlag_list();
    } 

    /*
     * Edit bestellvorschlag item
     * If id is empty, create a new one
     */
        
    function bestellvorschlag_edit() {
        $id = $this->app->Secure->GetGET('id');
        
        // Check if other users are editing this id
        if($this->app->erp->DisableModul('artikel',$id))
        {
          return;
        }   
              
        $this->app->Tpl->Set('ID', $id);

        $this->app->erp->MenuEintrag("index.php?module=bestellvorschlag&action=edit&id=$id", "Details");
        $this->app->erp->MenuEintrag("index.php?module=bestellvorschlag&action=list", "Zur&uuml;ck zur &Uuml;bersicht");
        $id = $this->app->Secure->GetGET('id');
        $input = $this->GetInput();
        $submit = $this->app->Secure->GetPOST('submit');
                
        if (empty($id)) {
            // New item
            $id = 'NULL';
        } 

        if ($submit != '')
        {

            // Write to database
            
            // Add checks here

            $columns = "id, ";
            $values = "$id, ";
            $update = "";
    
            $fix = "";

            foreach ($input as $key => $value) {
                $columns = $columns.$fix.$key;
                $values = $values.$fix."'".$value."'";
                $update = $update.$fix.$key." = '$value'";

                $fix = ", ";
            }

//            echo($columns."<br>");
//            echo($values."<br>");
//            echo($update."<br>");

            $sql = "INSERT INTO bestellvorschlag (".$columns.") VALUES (".$values.") ON DUPLICATE KEY UPDATE ".$update;

//            echo($sql);

            $this->app->DB->Update($sql);

            if ($id == 'NULL') {
                $msg = $this->app->erp->base64_url_encode("<div class=\"success\">Das Element wurde erfolgreich angelegt.</div>");
                header("Location: index.php?module=bestellvorschlag&action=list&msg=$msg");
            } else {
                $this->app->Tpl->Set('MESSAGE', "<div class=\"success\">Die Einstellungen wurden erfolgreich &uuml;bernommen.</div>");
            }
        }

    
        // Load values again from database
	$dropnbox = "'<img src=./themes/new/images/details_open.png class=details>' AS `open`, CONCAT('<input type=\"checkbox\" name=\"auswahl[]\" value=\"',b.id,'\" />') AS `auswahl`";
        $result = $this->app->DB->SelectArr("SELECT SQL_CALC_FOUND_ROWS b.id, $dropnbox, b.artikel, b.adresse, b.lager, b.id FROM bestellvorschlag b"." WHERE id=$id");

        foreach ($result[0] as $key => $value) {
            $this->app->Tpl->Set(strtoupper($key), $value);   
        }
             
        /*
         * Add displayed items later
         * 

        $this->app->Tpl->Add('KURZUEBERSCHRIFT2', $email);
        $this->app->Tpl->Add('EMAIL', $email);
        $this->app->Tpl->Add('ANGEZEIGTERNAME', $angezeigtername);         
         */

//        $this->SetInput($input);              
        $this->app->Tpl->Parse('PAGE', "bestellvorschlag_edit.tpl");
    }

    /**
     * Get all paramters from html form and save into $input
     */
    public function GetInput(): array {
        $input = array();
        //$input['EMAIL'] = $this->app->Secure->GetPOST('email');
        
        $input['artikel'] = $this->app->Secure->GetPOST('artikel');
	$input['adresse'] = $this->app->Secure->GetPOST('adresse');
	$input['lager'] = $this->app->Secure->GetPOST('lager');
	

        return $input;
    }

    /*
     * Set all fields in the page corresponding to $input
     */
    function SetInput($input) {
        // $this->app->Tpl->Set('EMAIL', $input['email']);        
        
        $this->app->Tpl->Set('ARTIKEL', $input['artikel']);
	$this->app->Tpl->Set('ADRESSE', $input['adresse']);
	$this->app->Tpl->Set('LAGER', $input['lager']);
	
    }

}
