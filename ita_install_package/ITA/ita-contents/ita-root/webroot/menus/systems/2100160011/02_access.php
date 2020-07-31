<?php
//   Copyright 2019 NEC Corporation
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.
//

    $tmpAry=explode('ita-root', dirname(__FILE__));$root_dir_path=$tmpAry[0].'ita-root';unset($tmpAry);
    //-- サイト個別PHP要素、ここから--
    //-- サイト個別PHP要素、ここまで--
    require_once ( $root_dir_path . "/libs/webcommonlibs/table_control_agent/web_parts_for_template_02_access.php");
    require_once ( $root_dir_path . "/libs/webcommonlibs/web_parts_for_common.php");
    //-- サイト個別PHP要素、ここから--
    //-- サイト個別PHP要素、ここまで--
    class Db_Access extends Db_Access_Core {
        //-- サイト個別PHP要素、ここから--

        /////////////////////
        // 新規登録
        /////////////////////
        function registerTable($menuData){
            // グローバル変数宣言
            global $g;

            // ローカル変数宣言
            $tranStartFlg = false;
            $arrayResult = array();

            
            try{

                require_once ( $g["root_dir_path"] . "/libs/webcommonlibs/table_control_agent/03_registerTable.php");
                require_once ( $g["root_dir_path"] . "/libs/backyardlibs/create_param_menu/ky_create_param_menu_classes.php");
                
                // トランザクション開始
                $varTrzStart = $g["objDBCA"]->transactionStart();
                if ($varTrzStart === false) {
                    web_log($g["objMTS"]->getSomeMessage("ITABASEH-ERR-900015", array(basename(__FILE__), __LINE__)));
                    $msg = $g["objMTS"]->getSomeMessage("ITABASEH-ERR-900015", array(basename(__FILE__), __LINE__));
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $tranStartFlg = true;

                $aryVariant = array("TCA_PRESERVED" => array("TCA_ACTION" => array("ACTION_MODE" => "DTUP_singleRecRegister")));
                
                // jsonからPHP配列に変換
                $menuData = json_decode($menuData,true);
                
                //////////////////////////
                // メニュー作成情報を登録
                //////////////////////////
                if(!array_key_exists("MENUGROUP_FOR_CMDB",$menuData['menu']))   $menuData['menu']['MENUGROUP_FOR_CMDB'] = "";
                if(!array_key_exists("MENUGROUP_FOR_HG",$menuData['menu']))     $menuData['menu']['MENUGROUP_FOR_HG'] = "";
                if(!array_key_exists("MENUGROUP_FOR_CONV",$menuData['menu']))   $menuData['menu']['MENUGROUP_FOR_CONV'] = "";
                
                $arrayRegisterData = array("MENU_NAME" => $menuData['menu']['MENU_NAME'],
                                           "TARGET" => $menuData['menu']['TARGET'],
                                           "DISP_SEQ" => $menuData['menu']['DISP_SEQ'],
                                           "PURPOSE" => $menuData['menu']['PURPOSE'],
                                           "MENUGROUP_FOR_CMDB" => $menuData['menu']['MENUGROUP_FOR_CMDB'],
                                           "MENUGROUP_FOR_HG" => $menuData['menu']['MENUGROUP_FOR_HG'],
                                           "MENUGROUP_FOR_H" => $menuData['menu']['MENUGROUP_FOR_H'],
                                           "MENUGROUP_FOR_VIEW" => $menuData['menu']['MENUGROUP_FOR_VIEW'],
                                           "MENUGROUP_FOR_CONV" => $menuData['menu']['MENUGROUP_FOR_CONV'],
                                           "DESCRIPTION" => $menuData['menu']['DESCRIPTION'],
                                           "NOTE" => $menuData['menu']['NOTE'],
                                          );
                
                $g["page_dir"] = "2100160001";

                // 登録処理
                $arrayResult = registerTableMain(2, $arrayRegisterData, "2100160001", 4);
                if($arrayResult[0] !== "000"){
                    throw new Exception();
                }
                // 登録したメニューを記録
                $menuData['menu']['CREATE_MENU_ID'] = json_decode($arrayResult[2],true)['CREATE_MENU_ID'];

                //////////////////////////
                // カラムグループ情報を登録
                //////////////////////////
                
                // カラムグループ情報テーブルを取得
                $columnGroupTable = new ColumnGroupTable($g["objDBCA"], $g["db_model_ch"]);
                $sql = $columnGroupTable->createSselect("WHERE DISUSE_FLAG = '0'");
                $result = $columnGroupTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg);
                    throw new Exception();
                }
                $cgArray = $result;
                
                foreach($menuData['group'] as &$groupData){
                    $groupData['PA_COL_GROUP_ID'] = "";
                    $skipFlag = false;
                    $fullPath = $groupData['PARENT'] . $groupData['COL_GROUP_NAME'];
                    foreach($cgArray as $cgData){
                        if($fullPath == $cgData['FULL_COL_GROUP_NAME']){
                            $skipFlag = true;
                            break;
                        }
                        if($cgData['FULL_COL_GROUP_NAME'] == $groupData['PARENT']){
                            $groupData['PA_COL_GROUP_ID'] = $cgData['COL_GROUP_ID'];
                        }
                    }
                    // 既存データがあった場合スキップ
                    if(true == $skipFlag){
                        continue;
                    }
                    $arrayRegisterData = array("PA_COL_GROUP_ID" => $groupData['PA_COL_GROUP_ID'],
                                               "COL_GROUP_NAME" => $groupData['COL_GROUP_NAME'],
                                               "DESCRIPTION" => ""
                                              );

                    $g["page_dir"] = "2100160008";

                    // 登録処理
                    $arrayResult = registerTableMain(2, $arrayRegisterData, "2100160008", 4);

                    if($arrayResult[0] !== "000"){
                        throw new Exception();
                    }
                    $cgArray[] = json_decode($arrayResult[2],true);
                }
                unset($groupData);
                
                //////////////////////////
                // メニュー作成項目情報を登録
                //////////////////////////
                foreach($menuData['item'] as &$itemData){
                    if($itemData['REQUIRED'] === true){
                        $required = "1";
                    }
                    else{
                        $required = "";
                    }
                    if($itemData['UNIQUED'] === true){
                        $uniqued= "1";
                    }
                    else{
                        $uniqued = "";
                    }
                    if($itemData['COL_GROUP_ID'] != ""){
                        foreach($cgArray as $cgData){
                            if($itemData['COL_GROUP_ID'] == $cgData['FULL_COL_GROUP_NAME']){
                                $itemData['COL_GROUP_ID'] = $cgData['COL_GROUP_ID'];
                                break;
                            }
                        }
                    }
                    if(!array_key_exists("MAX_LENGTH",$itemData))         $itemData["MAX_LENGTH"] = "";
                    if(!array_key_exists("PREG_MATCH",$itemData))         $itemData["PREG_MATCH"] = "";
                    if(!array_key_exists("MULTI_MAX_LENGTH",$itemData))   $itemData["MULTI_MAX_LENGTH"] = "";
                    if(!array_key_exists("MULTI_PREG_MATCH",$itemData))   $itemData["MULTI_PREG_MATCH"] = "";
                    if(!array_key_exists("INT_MIN",$itemData))            $itemData["INT_MIN"] = "";
                    if(!array_key_exists("INT_MAX",$itemData))            $itemData["INT_MAX"] = "";
                    if(!array_key_exists("FLOAT_MIN",$itemData))          $itemData["FLOAT_MIN"] = "";
                    if(!array_key_exists("FLOAT_MAX",$itemData))          $itemData["FLOAT_MAX"] = "";
                    if(!array_key_exists("FLOAT_DIGIT",$itemData))        $itemData["FLOAT_DIGIT"] = "";
                    if(!array_key_exists("OTHER_MENU_LINK_ID",$itemData))    $itemData["OTHER_MENU_LINK_ID"] = "";
                    
                    $arrayRegisterData = array("CREATE_MENU_ID" => $menuData['menu']['CREATE_MENU_ID'],
                                               "ITEM_NAME" => $itemData['ITEM_NAME'],
                                               "DISP_SEQ" => $itemData['DISP_SEQ'],
                                               "REQUIRED" => $required,
                                               "UNIQUED" => $uniqued,
                                               "COL_GROUP_ID" => $itemData['COL_GROUP_ID'],
                                               "INPUT_METHOD_ID" => $itemData['INPUT_METHOD_ID'],
                                               "MAX_LENGTH" => $itemData['MAX_LENGTH'],
                                               "PREG_MATCH" => $itemData['PREG_MATCH'],
                                               "MULTI_MAX_LENGTH" => $itemData['MULTI_MAX_LENGTH'],
                                               "MULTI_PREG_MATCH" => $itemData['MULTI_PREG_MATCH'],
                                               "INT_MIN" => $itemData['INT_MIN'],
                                               "INT_MAX" => $itemData['INT_MAX'],
                                               "FLOAT_MIN" => $itemData['FLOAT_MIN'],
                                               "FLOAT_MAX" => $itemData['FLOAT_MAX'],
                                               "FLOAT_DIGIT" => $itemData['FLOAT_DIGIT'],
                                               "OTHER_MENU_LINK_ID" => $itemData['OTHER_MENU_LINK_ID'],
                                               "DESCRIPTION" => $itemData['DESCRIPTION'],
                                               "NOTE" => $itemData['NOTE']
                                              );

                    $g["page_dir"] = "2100160002";

                    // 登録処理
                    $arrayResult = registerTableMain(2, $arrayRegisterData, "2100160002", 4);

                    if($arrayResult[0] !== "000"){
                        throw new Exception();
                    }
                    
                    $itemData['CREATE_ITEM_ID'] = json_decode($arrayResult[2],true)['CREATE_ITEM_ID'];
                }
                unset($itemData);
                
                //////////////////////////
                // 縦メニュー情報を登録
                //////////////////////////
                
                foreach($menuData['repeat'] as $repeatData){
                    $createItemID = $menuData['item'][$repeatData['COLUMNS'][0]]['CREATE_ITEM_ID'];
                    $arrayRegisterData = array("CREATE_ITEM_ID" => $createItemID,
                                               "COL_CNT" => count($repeatData['COLUMNS']),
                                               "REPEAT_CNT" => $repeatData['REPEAT_CNT']
                                              );

                    $g["page_dir"] = "2100160009";

                    // 登録処理
                    $arrayResult = registerTableMain(2, $arrayRegisterData, "2100160009", 4);

                    if($arrayResult[0] !== "000"){
                        throw new Exception();
                    }
                }

                // メニュー作成実行
                $createMenuStatusTable = new CreateMenuStatusTable($g["objDBCA"], $g["db_model_ch"]);
                $insertData = array();
                $insertData['CREATE_MENU_ID'] = $menuData['menu']['CREATE_MENU_ID'];
                $insertData['STATUS_ID'] = "1";
                $insertData['FILE_NAME'] = "";
                $insertData['NOTE'] = "";
                $insertData['DISUSE_FLAG'] = "0";
                $insertData['LAST_UPDATE_USER'] = $g['login_id'];
                
                //////////////////////////
                // メニュー作成管理テーブルに登録
                //////////////////////////
                $result = $createMenuStatusTable->insertTable($insertData, $seqNo, $jnlSeqNo);
                if(true !== $result){
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg);
                    throw new Exception();
                }
                $createResult = array("MM_STATUS_ID" => $seqNo,"CREATE_MENU_ID" => $menuData['menu']['CREATE_MENU_ID']);
                $arrayResult = array("000","",json_encode($createResult));

                // コミット
                $res = $g["objDBCA"]->transactionCommit();
                if ($res === false) {
                    web_log($g["objMTS"]->getSomeMessage("ITABASEH-ERR-900036", array(basename(__FILE__), __LINE__)));
                    $msg = $g["objMTS"]->getSomeMessage("ITABASEH-ERR-900036", array(basename(__FILE__), __LINE__));
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $tranStartFlg = false;

            }
            catch (Exception $e){
                if($tranStartFlg === true){
                    // ロールバック
                    $g["objDBCA"]->transactionRollback();
                }
            }

            if($arrayResult[0]=="000"){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-STD-4001",__FUNCTION__));
            }else if(intval($arrayResult[0])<500){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4002",__FUNCTION__));
                web_log( $arrayResult[2]);
            }else{
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4001",__FUNCTION__));
                web_log( $arrayResult[2]);
            }

            return makeAjaxProxyResultStream($arrayResult);
        }

        /////////////////////
        // 更新
        /////////////////////
        function updateTable($menuData){
            // グローバル変数宣言
            global $g;

            // ローカル変数宣言
            $arrayResult = array();
            try{
                require_once ( $g["root_dir_path"] . "/libs/webcommonlibs/table_control_agent/03_registerTable.php");
                require_once ( $g["root_dir_path"] . "/libs/webcommonlibs/table_control_agent/04_updateTable.php");
                require_once ( $g["root_dir_path"] . "/libs/webcommonlibs/table_control_agent/05_deleteTable.php");
                require_once ( $g["root_dir_path"] . "/libs/backyardlibs/create_param_menu/ky_create_param_menu_classes.php");
                
                // トランザクション開始
                $varTrzStart = $g["objDBCA"]->transactionStart();
                if ($varTrzStart === false) {
                    web_log($g["objMTS"]->getSomeMessage("ITABASEH-ERR-900015", array(basename(__FILE__), __LINE__)));
                    $msg = $g["objMTS"]->getSomeMessage("ITABASEH-ERR-900015", array(basename(__FILE__), __LINE__));
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $tranStartFlg = true;
                
                $aryVariant = array("TCA_PRESERVED" => array("TCA_ACTION" => array("ACTION_MODE" => "DTUP_singleRecRegister")));
                
                $menuData = json_decode($menuData,true);
                
                //////////////////////////
                // メニュー作成情報を取得
                //////////////////////////
                $createMenuInfoTable = new CreateMenuInfoTable($g['objDBCA'], $g['db_model_ch']);
                $sql = $createMenuInfoTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $createMenuInfoTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $createMenuInfoArray = $result;
                
                //////////////////////////
                // メニュー項目作成情報を取得
                //////////////////////////
                $createItemInfoTable = new CreateItemInfoTable($g['objDBCA'], $g['db_model_ch']);
                $sql = $createItemInfoTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $createItemInfoTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $createItemInfoArray = $result;
                
                //////////////////////////
                // カラムグループ管理テーブルを検索
                //////////////////////////
                $columnGroupTable = new ColumnGroupTable($g['objDBCA'], $g['db_model_ch']);
                $sql = $columnGroupTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $columnGroupTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $columnGroupArray = $result;
                
                //////////////////////////
                // メニュー(縦)作成情報を取得
                //////////////////////////
                $convertParamInfoTable = new ConvertParamInfoTable($g['objDBCA'], $g['db_model_ch']);
                $sql = $convertParamInfoTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $convertParamInfoTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $convertParamInfoArray = $result;
                
                //////////////////////////
                // メニュー作成情報を更新
                //////////////////////////
                if(!array_key_exists("MENUGROUP_FOR_CMDB",$menuData['menu']))   $menuData['menu']['MENUGROUP_FOR_CMDB'] = "";
                if(!array_key_exists("MENUGROUP_FOR_HG",$menuData['menu']))     $menuData['menu']['MENUGROUP_FOR_HG'] = "";
                if(!array_key_exists("MENUGROUP_FOR_CONV",$menuData['menu']))   $menuData['menu']['MENUGROUP_FOR_CONV'] = "";
                
                $arrayUpdateData = NULL;
                foreach($createMenuInfoArray as $createMenuInfoData){
                    if($createMenuInfoData['CREATE_MENU_ID'] == $menuData['menu']['CREATE_MENU_ID']){
                         $strNumberForRI = $menuData['menu']['CREATE_MENU_ID'];
                         $arrayUpdateData = array("MENU_NAME" => $menuData['menu']['MENU_NAME'],
                                           "TARGET" => $menuData['menu']['TARGET'],
                                           "DISP_SEQ" => $menuData['menu']['DISP_SEQ'],
                                           "PURPOSE" => $menuData['menu']['PURPOSE'],
                                           "MENUGROUP_FOR_CMDB" => $menuData['menu']['MENUGROUP_FOR_CMDB'],
                                           "MENUGROUP_FOR_HG" => $menuData['menu']['MENUGROUP_FOR_HG'],
                                           "MENUGROUP_FOR_H" => $menuData['menu']['MENUGROUP_FOR_H'],
                                           "MENUGROUP_FOR_VIEW" => $menuData['menu']['MENUGROUP_FOR_VIEW'],
                                           "MENUGROUP_FOR_CONV" => $menuData['menu']['MENUGROUP_FOR_CONV'],
                                           "DESCRIPTION" => $menuData['menu']['DESCRIPTION'],
                                           "NOTE" => $menuData['menu']['NOTE'],
                                           "UPD_UPDATE_TIMESTAMP" => "T_" . preg_replace("/[^a-zA-Z0-9]/", "", $menuData['menu']['LAST_UPDATE_TIMESTAMP'])
                                          );
                         break;
                    }
                }
                if($arrayUpdateData === NULL){
                    // 更新するメニューIDがいない場合、エラー
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5005', $result);
                    $arrayResult = array("999","",$msg);
                    throw new Exception();
                }
                $g["page_dir"] = "2100160001";

                // 更新処理
                $arrayResult = updateTableMain(3, $strNumberForRI, $arrayUpdateData, "2100160001", 4);
                if($arrayResult[0] !== "000"){
                    throw new Exception();
                }
                
                //////////////////////////
                // カラムグループ情報を更新
                ////////////////////////// 
                foreach($menuData['group'] as &$groupData){
                    $groupData['PA_COL_GROUP_ID'] = "";
                    $skipFlag = false;
                    if($groupData['PARENT'] == "")
                        $fullPath = $groupData['COL_GROUP_NAME'];
                    else{
                        $fullPath = $groupData['PARENT'] . '/' .  $groupData['COL_GROUP_NAME'];
                    }
                    foreach($columnGroupArray as $columnGroupData){
                        if($fullPath == $columnGroupData['FULL_COL_GROUP_NAME']){
                            $skipFlag = true;
                            break;
                        }
                        if($columnGroupData['FULL_COL_GROUP_NAME'] == $groupData['PARENT']){
                            $groupData['PA_COL_GROUP_ID'] = $columnGroupData['COL_GROUP_ID'];
                        }
                    }
                    // 既存データがあった場合スキップ
                    if(true == $skipFlag){
                        continue;
                    }
                    $arrayRegisterData = array("PA_COL_GROUP_ID" => $groupData['PA_COL_GROUP_ID'],
                                               "COL_GROUP_NAME" => $groupData['COL_GROUP_NAME'],
                                               "DESCRIPTION" => ""
                                              );

                    $g["page_dir"] = "2100160008";

                    // 登録処理
                    $arrayResult = registerTableMain(2, $arrayRegisterData, "2100160008", 4);

                    if($arrayResult[0] !== "000"){
                        throw new Exception();
                    }
                    $columnGroupArray[] = json_decode($arrayResult[2],true);
                }
                unset($groupData);

                //////////////////////////
                // メニュー項目情報を更新
                ////////////////////////// 
                
                // 更新したカラムグループ管理テーブルを検索
                $sql = $columnGroupTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $columnGroupTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g['objMTS']->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    throw new Exception($msg);
                }
                $columnGroupArray = $result;
                
                // 既存、使えなくなった項目を廃止
                foreach($createItemInfoArray as $createItemInfoData){
                    if($createItemInfoData['CREATE_MENU_ID'] == $menuData['menu']['CREATE_MENU_ID']){
                        $key = array_search($createItemInfoData['CREATE_ITEM_ID'], array_column($menuData['item'], 'CREATE_ITEM_ID'));
                        if($key === false){
                            $strNumberForRI = $createItemInfoData['CREATE_ITEM_ID'];       // 主キー
                            $reqDeleteData = array("DISUSE_FLAG"          => "0",
                                                   "UPD_UPDATE_TIMESTAMP" => "T_" . preg_replace("/[^a-zA-Z0-9]/", "", $createItemInfoData['item']['LAST_UPDATE_TIMESTAMP'])
                                                  );

                            $g["page_dir"] = "2100160002";

                            // 廃止処理
                            $intBaseMode = 3;       // 3:廃止、5:復活
                            $arrayResult = deleteTableMain($intBaseMode, $strNumberForRI, $reqDeleteData, "2100160002", 4);
                            if($arrayResult[0] !== "000"){
                                throw new Exception();
                            }
                        }
                    }
                }
                // IDがいる項目を更新
                foreach($menuData['item'] as &$itemData){
                    if(!array_key_exists('CREATE_ITEM_ID',$itemData)){
                        $itemData['CREATE_ITEM_ID'] = "";
                    }
                    if($itemData['CREATE_ITEM_ID'] != ""){
                        if($itemData['REQUIRED'] === true){
                            $required = "1";
                        }
                        else{
                            $required = "";
                        }
                        if($itemData['UNIQUED'] === true){
                            $uniqued= "1";
                        }
                        else{
                            $uniqued = "";
                        }
                        if($itemData['COL_GROUP_ID'] != ""){
                            foreach($columnGroupArray as $columnGroupData){
                                if($itemData['COL_GROUP_ID'] == $columnGroupData['FULL_COL_GROUP_NAME']){
                                    $itemData['COL_GROUP_ID'] = $columnGroupData['COL_GROUP_ID'];
                                    break;
                                }
                            }
                        }
                        if(!array_key_exists("MAX_LENGTH",$itemData))         $itemData["MAX_LENGTH"] = "";
                        if(!array_key_exists("PREG_MATCH",$itemData))         $itemData["PREG_MATCH"] = "";
                        if(!array_key_exists("MULTI_MAX_LENGTH",$itemData))   $itemData["MULTI_MAX_LENGTH"] = "";
                        if(!array_key_exists("MULTI_PREG_MATCH",$itemData))   $itemData["MULTI_PREG_MATCH"] = "";
                        if(!array_key_exists("INT_MIN",$itemData))            $itemData["INT_MIN"] = "";
                        if(!array_key_exists("INT_MAX",$itemData))            $itemData["INT_MAX"] = "";
                        if(!array_key_exists("FLOAT_MIN",$itemData))          $itemData["FLOAT_MIN"] = "";
                        if(!array_key_exists("FLOAT_MAX",$itemData))          $itemData["FLOAT_MAX"] = "";
                        if(!array_key_exists("FLOAT_DIGIT",$itemData))        $itemData["FLOAT_DIGIT"] = "";
                        if(!array_key_exists("OTHER_MENU_LINK_ID",$itemData))    $itemData["OTHER_MENU_LINK_ID"] = "";
                        
                        $strNumberForRI = $itemData['CREATE_ITEM_ID'];
                        $arrayUpdateData = array("CREATE_MENU_ID"   => $menuData['menu']['CREATE_MENU_ID'],
                                                 "ITEM_NAME"        => $itemData['ITEM_NAME'],
                                                 "DISP_SEQ"         => $itemData['DISP_SEQ'],
                                                 "REQUIRED"         => $required,
                                                 "UNIQUED"          => $uniqued,
                                                 "COL_GROUP_ID"     => $itemData['COL_GROUP_ID'],
                                                 "INPUT_METHOD_ID"  => $itemData['INPUT_METHOD_ID'],
                                                 "MAX_LENGTH"       => $itemData['MAX_LENGTH'],
                                                 "PREG_MATCH"       => $itemData['PREG_MATCH'],
                                                 "MULTI_MAX_LENGTH" => $itemData['MULTI_MAX_LENGTH'],
                                                 "MULTI_PREG_MATCH" => $itemData['MULTI_PREG_MATCH'],
                                                 "INT_MIN"          => $itemData['INT_MIN'],
                                                 "INT_MAX"          => $itemData['INT_MAX'],
                                                 "FLOAT_MIN"        => $itemData['FLOAT_MIN'],
                                                 "FLOAT_MAX"        => $itemData['FLOAT_MAX'],
                                                 "FLOAT_DIGIT"      => $itemData['FLOAT_DIGIT'],
                                                 "OTHER_MENU_LINK_ID"  => $itemData['OTHER_MENU_LINK_ID'],
                                                 "DESCRIPTION"      => $itemData['DESCRIPTION'],
                                                 "NOTE"             => $itemData['NOTE'],
                                                 "UPD_UPDATE_TIMESTAMP" => "T_" . preg_replace("/[^a-zA-Z0-9]/", "", $itemData['LAST_UPDATE_TIMESTAMP'])
                                                 );

                        $g["page_dir"] = "2100160002";
                        // 更新処理
                        $arrayResult = updateTableMain(3, $strNumberForRI, $arrayUpdateData, "2100160002", 4);
                        if($arrayResult[0] !== "000"){
                            throw new Exception();
                        }
                        $itemData['CREATE_ITEM_ID'] = json_decode($arrayResult[2],true)['CREATE_ITEM_ID'];
                    }
                }
                unset($itemData);
                
                // IDがいない項目を新規登録
                foreach($menuData['item'] as &$itemData){
                    if($itemData['CREATE_ITEM_ID'] == ""){
                        if($itemData['REQUIRED'] === true){
                            $required = "1";
                        }
                        else{
                            $required = "";
                        }
                        if($itemData['UNIQUED'] === true){
                            $uniqued= "1";
                        }
                        else{
                            $uniqued = "";
                        }
                        if($itemData['COL_GROUP_ID'] != ""){
                            foreach($columnGroupArray as $columnGroupData){
                                if($itemData['COL_GROUP_ID'] == $columnGroupData['FULL_COL_GROUP_NAME']){
                                    $itemData['COL_GROUP_ID'] = $columnGroupData['COL_GROUP_ID'];
                                    break;
                                }
                            }
                        }
                        if(!array_key_exists("MAX_LENGTH",$itemData))         $itemData["MAX_LENGTH"] = "";
                        if(!array_key_exists("PREG_MATCH",$itemData))         $itemData["PREG_MATCH"] = "";
                        if(!array_key_exists("MULTI_MAX_LENGTH",$itemData))   $itemData["MULTI_MAX_LENGTH"] = "";
                        if(!array_key_exists("MULTI_PREG_MATCH",$itemData))   $itemData["MULTI_PREG_MATCH"] = "";
                        if(!array_key_exists("INT_MIN",$itemData))            $itemData["INT_MIN"] = "";
                        if(!array_key_exists("INT_MAX",$itemData))            $itemData["INT_MAX"] = "";
                        if(!array_key_exists("FLOAT_MIN",$itemData))          $itemData["FLOAT_MIN"] = "";
                        if(!array_key_exists("FLOAT_MAX",$itemData))          $itemData["FLOAT_MAX"] = "";
                        if(!array_key_exists("FLOAT_DIGIT",$itemData))        $itemData["FLOAT_DIGIT"] = "";
                        if(!array_key_exists("OTHER_MENU_LINK_ID",$itemData))    $itemData["OTHER_MENU_LINK_ID"] = "";
                        
                        $arrayRegisterData = array("CREATE_MENU_ID"   => $menuData['menu']['CREATE_MENU_ID'],
                                                   "ITEM_NAME"        => $itemData['ITEM_NAME'],
                                                   "DISP_SEQ"         => $itemData['DISP_SEQ'],
                                                   "REQUIRED"         => $required,
                                                   "UNIQUED"          => $uniqued,
                                                   "COL_GROUP_ID"     => $itemData['COL_GROUP_ID'],
                                                   "INPUT_METHOD_ID"  => $itemData['INPUT_METHOD_ID'],
                                                   "MAX_LENGTH"       => $itemData['MAX_LENGTH'],
                                                   "PREG_MATCH"       => $itemData['PREG_MATCH'],
                                                   "MULTI_MAX_LENGTH" => $itemData['MULTI_MAX_LENGTH'],
                                                   "MULTI_PREG_MATCH" => $itemData['MULTI_PREG_MATCH'],
                                                   "INT_MIN"          => $itemData['INT_MIN'],
                                                   "INT_MAX"          => $itemData['INT_MAX'],
                                                   "FLOAT_MIN"        => $itemData['FLOAT_MIN'],
                                                   "FLOAT_MAX"        => $itemData['FLOAT_MAX'],
                                                   "FLOAT_DIGIT"      => $itemData['FLOAT_DIGIT'],
                                                   "OTHER_MENU_LINK_ID"  => $itemData['OTHER_MENU_LINK_ID'],
                                                   "DESCRIPTION"      => $itemData['DESCRIPTION'],
                                                   "NOTE"             => $itemData['NOTE']
                                                  );

                        $g["page_dir"] = "2100160002";

                        // 登録処理
                        $arrayResult = registerTableMain(2, $arrayRegisterData, "2100160002", 4);

                        if($arrayResult[0] !== "000"){
                            throw new Exception();
                        }
                        $itemData['CREATE_ITEM_ID'] = json_decode($arrayResult[2],true)['CREATE_ITEM_ID'];
                    }
                }
                unset($itemData);
                
                //////////////////////////
                // 縦メニュー情報を更新
                ////////////////////////// 

                $updateData = NULL;
                // 既存の縦メニュー項目を探す
                foreach($convertParamInfoArray as $convertParamInfoData){
                    $key = array_search($convertParamInfoData['CREATE_ITEM_ID'], array_column($createItemInfoArray, 'CREATE_ITEM_ID'));
                    if($key !== false && $createItemInfoArray[$key] == $menuData['menu']['CREATE_MENU_ID']){
                        $updateData = $convertParamInfoData;
                    }
                }
                // 既存の縦メニュー項目を廃止
                if(count($menuData['repeat']) == 0 && $updateData != NULL){
                    $strNumberForRI = $updateData['CONVERT_PARAM_ID'];       // 主キー
                    $reqDeleteData = array("DISUSE_FLAG"          => "0",
                                           "UPD_UPDATE_TIMESTAMP" => "T_" . preg_replace("/[^a-zA-Z0-9]/", "", $updateData['LAST_UPDATE_TIMESTAMP'])
                                          );

                    $g["page_dir"] = "2100160009";

                    // 廃止処理
                    $intBaseMode = 3;       // 3:廃止、5:復活
                    $arrayResult = deleteTableMain($intBaseMode, $strNumberForRI, $reqDeleteData, "2100160009", 4);
                    if($arrayResult[0] !== "000"){
                        throw new Exception();
                    }
                }
                // 既存の縦メニュー項目を更新
                else if(count($menuData['repeat']) == 1 && $updateData != NULL){
                    foreach($menuData['repeat'] as $repeatData){
                        $strNumberForRI = $updateData['CONVERT_PARAM_ID'];
                        $createItemID = $menuData['item'][$repeatData['COLUMNS'][0]]['CREATE_ITEM_ID'];
                        $arrayRegisterData = array("CREATE_ITEM_ID" => $createItemID,
                                                   "COL_CNT" => count($repeatData['COLUMNS']),
                                                   "REPEAT_CNT" => $repeatData['REPEAT_CNT'],
                                                   "UPD_UPDATE_TIMESTAMP" => "T_" . preg_replace("/[^a-zA-Z0-9]/", "", $menuData['repeat']['r1']['LAST_UPDATE_TIMESTAMP'])
                                                  );

                        $g["page_dir"] = "2100160009";

                        // 更新処理
                        $arrayResult = updateTableMain(3, $strNumberForRI, $arrayUpdateData, "2100160009", 4);
                        if($arrayResult[0] !== "000"){
                            throw new Exception();
                        }
                    }
                }
                // 新規縦メニュー項目を登録
                else if(count($menuData['repeat']) == 1 && $updateData == NULL){
                    foreach($menuData['repeat'] as $repeatData){
                        $createItemID = $menuData['item'][$repeatData['COLUMNS'][0]]['CREATE_ITEM_ID'];
                        $arrayRegisterData = array("CREATE_ITEM_ID" => $createItemID,
                                                   "COL_CNT" => count($repeatData['COLUMNS']),
                                                   "REPEAT_CNT" => $repeatData['REPEAT_CNT']
                                                  );

                        $g["page_dir"] = "2100160009";

                        // 登録処理
                        $arrayResult = registerTableMain(2, $arrayRegisterData, "2100160009", 4);

                        if($arrayResult[0] !== "000"){
                            throw new Exception();
                        }
                    }
                }
                
                
                //////////////////////////
                // メニュー作成管理テーブルに登録
                //////////////////////////
                $createMenuStatusTable = new CreateMenuStatusTable($g["objDBCA"], $g["db_model_ch"]);
                $insertData = array();
                $insertData['CREATE_MENU_ID'] = $menuData['menu']['CREATE_MENU_ID'];
                $insertData['STATUS_ID'] = "1";
                $insertData['FILE_NAME'] = "";
                $insertData['NOTE'] = "";
                $insertData['DISUSE_FLAG'] = "0";
                $insertData['LAST_UPDATE_USER'] = $g['login_id'];
                
                $result = $createMenuStatusTable->insertTable($insertData, $seqNo, $jnlSeqNo);
                if(true !== $result){
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $createResult = array("MM_STATUS_ID" => $seqNo,"CREATE_MENU_ID" => $menuData['menu']['CREATE_MENU_ID']);
                $arrayResult = array("000","",json_encode($createResult));

                // コミット
                $res = $g["objDBCA"]->transactionCommit();
                if ($res === false) {
                    web_log($g["objMTS"]->getSomeMessage("ITABASEH-ERR-900036", array(basename(__FILE__), __LINE__)));
                    $msg = $g["objMTS"]->getSomeMessage("ITABASEH-ERR-900036", array(basename(__FILE__), __LINE__));
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $tranStartFlg = false;
            }
            catch(Exception $e){
                if($tranStartFlg === true){
                    // ロールバック
                    $g["objDBCA"]->transactionRollback();
                }
            }
            if($arrayResult[0]=="000"){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-STD-4001",__FUNCTION__));
            }else if(intval($arrayResult[0])<500){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4002",__FUNCTION__));
                web_log( $arrayResult[2]);
            }else{
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4001",__FUNCTION__));
                web_log( $arrayResult[2]);
            }

            return makeAjaxProxyResultStream($arrayResult);
        }

        /////////////////////
        // 入力方式リスト取得
        /////////////////////
        function selectInputMethod(){
            // グローバル変数宣言
            global $g;

            // ローカル変数宣言
            $arrayResult = array();

            require_once ( $g["root_dir_path"] . "/libs/backyardlibs/create_param_menu/ky_create_param_menu_classes.php");

            $inputMethodTable = new inputMethodTable($g["objDBCA"], $g["db_model_ch"]);
            $sql = $inputMethodTable->createSselect("WHERE DISUSE_FLAG = '0'");
            $result = $inputMethodTable->selectTable($sql);
            if(!is_array($result)){
                $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                $arrayResult = array("999","", $msg);
                return makeAjaxProxyResultStream($arrayResult);
            }
            $filteredData = array();
            foreach($result as $imData){
                $addArray = array();
                $addArray['INPUT_METHOD_ID']   = $imData['INPUT_METHOD_ID'];
                $addArray['INPUT_METHOD_NAME'] = $imData['INPUT_METHOD_NAME'];
                $filteredData[] = $addArray;
            }
            
            $arrayResult = array("000","", json_encode($filteredData));
            
            if($arrayResult[0]=="000"){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-STD-4001",__FUNCTION__));
            }else if(intval($arrayResult[0])<500){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4002",__FUNCTION__));
            }else{
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4001",__FUNCTION__));
            }

            return makeAjaxProxyResultStream($arrayResult);
        }

        /////////////////////
        // 作成対象リスト取得
        /////////////////////
        function selectParamTarget(){
            // グローバル変数宣言
            global $g;

            // ローカル変数宣言
            $arrayResult = array();

            require_once ( $g["root_dir_path"] . "/libs/backyardlibs/create_param_menu/ky_create_param_menu_classes.php");

            $paramTargetTable = new ParamTargetTable($g["objDBCA"], $g["db_model_ch"]);
            $sql = $paramTargetTable->createSselect("WHERE DISUSE_FLAG = '0'");
            $result = $paramTargetTable->selectTable($sql);
            if(!is_array($result)){
                $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                $arrayResult = array("999","", $msg);
                return makeAjaxProxyResultStream($arrayResult);
            }
            $filteredData = array();
            foreach($result as $ptData){
                $addArray = array();
                $addArray['TARGET_ID']   = $ptData['TARGET_ID'];
                $addArray['TARGET_NAME'] = $ptData['TARGET_NAME'];
                $filteredData[] = $addArray;
            }
            
            $arrayResult = array("000","", json_encode($filteredData));

            if($arrayResult[0]=="000"){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-STD-4001",__FUNCTION__));
            }else if(intval($arrayResult[0])<500){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4002",__FUNCTION__));
            }else{
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4001",__FUNCTION__));
            }

            return makeAjaxProxyResultStream($arrayResult);
        }

        /////////////////////
        // 用途リスト取得
        /////////////////////
        function selectParamPurpose(){
            // グローバル変数宣言
            global $g;

            // ローカル変数宣言
            $arrayResult = array();
            require_once ( $g["root_dir_path"] . "/libs/backyardlibs/create_param_menu/ky_create_param_menu_classes.php");

            $paramPurposeTable = new ParamPurposeTable($g["objDBCA"], $g["db_model_ch"]);
            $sql = $paramPurposeTable->createSselect("WHERE DISUSE_FLAG = '0'");
            $result = $paramPurposeTable->selectTable($sql);
            if(!is_array($result)){
                $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                $arrayResult = array("999","", $msg);
                return makeAjaxProxyResultStream($arrayResult);
            }
            $filteredData = array();
            foreach($result as $ppData){
                $addArray = array();
                $addArray['PURPOSE_ID']   = $ppData['PURPOSE_ID'];
                $addArray['PURPOSE_NAME'] = $ppData['PURPOSE_NAME'];
                $filteredData[] = $addArray;
            }
            
            $arrayResult = array("000","", json_encode($filteredData));

            if($arrayResult[0]=="000"){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-STD-4001",__FUNCTION__));
            }else if(intval($arrayResult[0])<500){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4002",__FUNCTION__));
            }else{
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4001",__FUNCTION__));
            }

            return makeAjaxProxyResultStream($arrayResult);
        }

        /////////////////////
        // メニューグループリスト取得
        /////////////////////
        function selectMenuGroupList(){
            // グローバル変数宣言
            global $g;

            // ローカル変数宣言
            $arrayResult = array();
            
            require_once ( $g["root_dir_path"] . "/libs/backyardlibs/create_param_menu/ky_create_param_menu_classes.php");
            
            $menuGroupTable = new MenuGroupTable($g["objDBCA"], $g["db_model_ch"]);
            $sql = $menuGroupTable->createSselect("WHERE DISUSE_FLAG = '0'");
            $result = $menuGroupTable->selectTable($sql);
            if(!is_array($result)){
                $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                $arrayResult = array("999","", $msg);
                return makeAjaxProxyResultStream($arrayResult);
            }
            $filteredData = array();
            foreach($result as $mgData){
                if($mgData['MENU_GROUP_ID'] < "2100000001"){
                    $addArray = array();
                    $addArray['MENU_GROUP_ID']   = $mgData['MENU_GROUP_ID'];
                    $addArray['MENU_GROUP_NAME'] = $mgData['MENU_GROUP_NAME'];
                    $filteredData[] = $addArray;
                }
            }
            
            $arrayResult = array("000","", json_encode($filteredData));

            if($arrayResult[0]=="000"){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-STD-4001",__FUNCTION__));
            }else if(intval($arrayResult[0])<500){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4002",__FUNCTION__));
            }else{
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4001",__FUNCTION__));
            }

            return makeAjaxProxyResultStream($arrayResult);
        }

        /////////////////////
        // プルダウン選択項目リスト取得
        /////////////////////
        function selectPulldownList(){
            // グローバル変数宣言
            global $g;
            
            // ローカル変数宣言
            $arrayResult = array();

            require_once ( $g["root_dir_path"] . "/libs/backyardlibs/create_param_menu/ky_create_param_menu_classes.php");
            $pullDownTable = new PullDownTable($g["objDBCA"], $g["db_model_ch"]);
            $sql = $pullDownTable->createSselect("WHERE DISUSE_FLAG = '0'");
            $result = $pullDownTable->selectTable($sql);
            if(!is_array($result)){
                $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                $arrayResult = array("999","", $result);
                return makeAjaxProxyResultStream($arrayResult);
            }
            $filteredData = array();

            foreach($result as $pdData){
                $addArray = array();
                $addArray['LINK_ID']       = $pdData['LINK_ID'];
                $addArray['LINK_PULLDOWN'] = $pdData['LINK_PULLDOWN'];
                $filteredData[] = $addArray;
            }
            $arrayResult = array("000","", json_encode($filteredData));

            if($arrayResult[0]=="000"){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-STD-4001",__FUNCTION__));
            }else if(intval($arrayResult[0])<500){
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4002",__FUNCTION__));
            }else{
                web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4001",__FUNCTION__));
            }

            return makeAjaxProxyResultStream($arrayResult);
        }
        
        /////////////////////
        // メニュー作成情報関連データ取得
        /////////////////////
        function selectMenuInfo($createMenuId){
        
            // グローバル変数宣言
            global $g;

            // ローカル変数宣言
            $arrayResult = array();
            $returnDataArray = array();

            require_once ( $g["root_dir_path"] . "/libs/backyardlibs/create_param_menu/ky_create_param_menu_classes.php");
            
            try{
                //////////////////////////
                // メニュー作成情報を取得
                //////////////////////////
                $createMenuInfoTable = new CreateMenuInfoTable($g['objDBCA'], $g['db_model_ch']);
                $sql = $createMenuInfoTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $createMenuInfoTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g['objMTS']->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $createMenuInfoArray = $result;
                
                //////////////////////////
                // メニュー項目作成情報を取得
                //////////////////////////
                $createItemInfoTable = new CreateItemInfoTable($g['objDBCA'], $g['db_model_ch']);
                $sql = $createItemInfoTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $createItemInfoTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g['objMTS']->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $createItemInfoArray = $result;
                
                //////////////////////////
                // カラムグループ管理テーブルを検索
                //////////////////////////
                $columnGroupTable = new ColumnGroupTable($g['objDBCA'], $g['db_model_ch']);
                $sql = $columnGroupTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $columnGroupTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g['objMTS']->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg); 
                    throw new Exception();
                }
                $columnGroupArray = $result;
                
                /////////////////////
                // アカウントリスト取得
                /////////////////////
                $accountListTable = new AccountListTable($g["objDBCA"], $g["db_model_ch"]);
                $sql = $accountListTable->createSselect("WHERE DISUSE_FLAG = '0'");
                
                // SQL実行
                $result = $accountListTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g["objMTS"]->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","", $msg);
                    throw new Exception();
                }
                $accountListArray = $result;
                
                //////////////////////////
                // メニュー(縦)作成情報を取得
                //////////////////////////
                $convertParamInfoTable = new ConvertParamInfoTable($g['objDBCA'], $g['db_model_ch']);
                $sql = $convertParamInfoTable->createSselect("WHERE DISUSE_FLAG = '0'");

                // SQL実行
                $result = $convertParamInfoTable->selectTable($sql);
                if(!is_array($result)){
                    $msg = $g['objMTS']->getSomeMessage('ITACREPAR-ERR-5003', $result);
                    $arrayResult = array("999","",$msg);
                    throw new Exception();
                }
                $convertParamInfoArray = $result;
                
                // メニュー情報詰め込み
                $findFlag = false;
                foreach($createMenuInfoArray as $createMenuInfoData){
                    if($createMenuInfoData['CREATE_MENU_ID'] == $createMenuId){
                        $findFlag = true;
                        $username = "";
                        foreach($accountListArray as $accountListData){
                            if($createMenuInfoData['LAST_UPDATE_USER'] == $accountListData['USER_ID']){
                                $username = $accountListData['USERNAME_JP'];
                            }
                        }
                        
                        $date = DateTime::createFromFormat('Y-m-d H:i:s.u', $createMenuInfoData['LAST_UPDATE_TIMESTAMP']);
                        
                        $returnDataArray['menu'] = array(
                            "CREATE_MENU_ID"           => $createMenuInfoData['CREATE_MENU_ID'],
                            "MENU_NAME"                => $createMenuInfoData['MENU_NAME'],
                            "PURPOSE"                  => $createMenuInfoData['PURPOSE'],
                            "TARGET"                   => $createMenuInfoData['TARGET'],
                            "MENUGROUP_FOR_HG"         => $createMenuInfoData['MENUGROUP_FOR_HG'],
                            "MENUGROUP_FOR_H"          => $createMenuInfoData['MENUGROUP_FOR_H'],
                            "MENUGROUP_FOR_VIEW"       => $createMenuInfoData['MENUGROUP_FOR_VIEW'],
                            "MENUGROUP_FOR_CONV"       => $createMenuInfoData['MENUGROUP_FOR_CONV'],
                            "MENUGROUP_FOR_CMDB"       => $createMenuInfoData['MENUGROUP_FOR_CMDB'],
                            "DISP_SEQ"                 => $createMenuInfoData['DISP_SEQ'],
                            "DESCRIPTION"              => $createMenuInfoData['DESCRIPTION'],
                            "NOTE"                     => $createMenuInfoData['NOTE'],
                            "LAST_UPDATE_USER"         => $username,
                            "LAST_UPDATE_TIMESTAMP"    => $createMenuInfoData['LAST_UPDATE_TIMESTAMP'],
                            "LAST_UPDATE_TIMESTAMP_FOR_DISPLAY" => $date->format('Y-m-d H:i:s')
                        );
                        break;
                    }
                }
                
                // 対応のメニュー情報がない場合、エラー
                if(false === $findFlag){
                    $msg = $g['objMTS']->getSomeMessage('ITACREPAR-ERR-5005', $result);
                    $arrayResult = array("999","",$msg);
                    throw new Exception($msg);
                }
                
                
                // メニュー項目情報を特定する
                $itemInfoArray = array();
                foreach($createItemInfoArray as $ciiData){
                    if($createMenuId == $ciiData['CREATE_MENU_ID']){
                        $itemInfoArray[] = $ciiData;
                    }
                }
                
                // 項目作成情報を表示順序、項番の昇順に並べ替える
                $dispSeqArray = array();
                $idArray = array();
                foreach ($itemInfoArray as $key => $itemInfo){
                    $dispSeqArray[$key] = $itemInfo['DISP_SEQ'];
                    $idArray[$key]      = $itemInfo['CREATE_ITEM_ID'];
                }
                array_multisort($dispSeqArray, SORT_ASC, $idArray, SORT_ASC, $itemInfoArray);
                
                // 縦メニュー
                $convertFlag = false;
                $cpiData = NULL;
                // 開始項目を探す
                foreach($convertParamInfoArray as $convertParamInfoData){
                    $searchIdx = array_search($convertParamInfoData['CREATE_ITEM_ID'], array_column($itemInfoArray, 'CREATE_ITEM_ID'));
                    if(false !== $searchIdx){
                        $cpiData = $convertParamInfoData;
                        $convertFlag = true;
                        break;
                    }
                }
                
                if(NULL === $cpiData){
                    $returnDataArray['repeat'][] = array();
                }
                else{
                    $columnsArray = array();
                    for($i = 1 ; $i <= $cpiData['COL_CNT'] ; $i++){
                        $columnsArray[] = "i" . ($i + $searchIdx);
                    }
                    $returnDataArray['repeat']['r1'] = array(
                        "columns"    => $columnsArray,
                        "REPEAT_CNT" => $cpiData['REPEAT_CNT'],
                        "LAST_UPDATE_TIMESTAMP" => $cpiData['LAST_UPDATE_TIMESTAMP']
                    );
                }
                
                // 項目作成情報
                $tmpGroupArray = array();
                $itemNum = 1;
                foreach($itemInfoArray as $itemInfoData){
                    // 親カラムグループを探す
                    $parent = "";
                    if($itemInfoData['COL_GROUP_ID'] != ""){
                        // 使われてカラムグループを記録
                        $tmpGroupArray[$itemInfoData['COL_GROUP_ID']][] = 'i' . $itemNum;
                        foreach($columnGroupArray as $columnGroupData){
                            if($columnGroupData['COL_GROUP_ID'] == $itemInfoData['COL_GROUP_ID']){
                                $parent = $columnGroupData['FULL_COL_GROUP_NAME'];
                                break;
                            }
                        }
                    }
                    if($itemInfoData['REQUIRED'] == "1"){
                        $required = true;
                    }
                    else{
                        $required = false;
                    }
                    if($itemInfoData['UNIQUED'] == "1"){
                        $uniqued= true;
                    }
                    else{
                        $uniqued = false;
                    }
                    if($convertFlag == true && $itemNum >= $searchIdx + $cpiData['COL_CNT'] + 1 && $itemNum < $searchIdx + $cpiData['COL_CNT'] * $cpiData['REPEAT_CNT'] + 1){
                        $repeatItem = true;
                    }
                    else{
                        $repeatItem = false;
                    }
                    $returnDataArray['item']['i' . $itemNum] = array(
                        "CREATE_MENU_ID"        => $itemInfoData['CREATE_MENU_ID'],
                        "CREATE_ITEM_ID"        => $itemInfoData['CREATE_ITEM_ID'],
                        "ITEM_NAME"             => $itemInfoData['ITEM_NAME'],
                        "DISP_SEQ"              => $itemInfoData['DISP_SEQ'],
                        "REQUIRED"              => $required,
                        "UNIQUED"               => $uniqued,
                        "COL_GROUP_ID"          => $itemInfoData['COL_GROUP_ID'],
                        "PARENT"                => $parent,
                        "INPUT_METHOD_ID"       => $itemInfoData['INPUT_METHOD_ID'],
                        "MAX_LENGTH"            => $itemInfoData['MAX_LENGTH'],
                        "PREG_MATCH"            => $itemInfoData['PREG_MATCH'],
                        "MULTI_MAX_LENGTH"      => $itemInfoData['MULTI_MAX_LENGTH'],
                        "MULTI_PREG_MATCH"      => $itemInfoData['MULTI_PREG_MATCH'],
                        "INT_MIN"               => $itemInfoData['INT_MIN'],
                        "INT_MAX"               => $itemInfoData['INT_MAX'],
                        "FLOAT_MIN"             => $itemInfoData['FLOAT_MIN'],
                        "FLOAT_MAX"             => $itemInfoData['FLOAT_MAX'],
                        "FLOAT_DIGIT"           => $itemInfoData['FLOAT_DIGIT'],
                        "OTHER_MENU_LINK_ID"    => $itemInfoData['OTHER_MENU_LINK_ID'],
                        "DESCRIPTION"           => $itemInfoData['DESCRIPTION'],
                        "REPEAT_ITEM"           => $repeatItem,
                        "MIN_WIDTH"             => "",
                        "NOTE"                  => $itemInfoData['NOTE'],
                        "LAST_UPDATE_TIMESTAMP" => $itemInfoData['LAST_UPDATE_TIMESTAMP']
                    );
                    $itemNum++; 
                }
                
                // カラムグループ
                // 
                $checked = array(); 
                // ルート親カラムグループまで探す
                foreach($tmpGroupArray as $key => $groupData){
                    $curGroup = $key;
                    $endFlag = false;
                    while(false == $endFlag){
                        foreach($columnGroupArray as $columnGroupData){
                            if($columnGroupData['COL_GROUP_ID'] == $curGroup){
                                if($columnGroupData['PA_COL_GROUP_ID'] == "" || true === in_array($curGroup,$checked)){
                                    $endFlag = true;
                                    break;
                                }
                                else{
                                    $tmpGroupArray[$columnGroupData['PA_COL_GROUP_ID']][] = $curGroup;
                                    $checked[] = $curGroup;
                                    $curGroup = $columnGroupData['PA_COL_GROUP_ID'];
                                    break;
                                }
                            }
                        }
                    }
                }
                
                $keyToId = array(); // {COL_GROUP_ID -> g1,g2,g3...}
                $returnGroupArray = array(); // WEBに返信するカラムグループ
                $groupNum = 1;
                // COL_GROUP_IDと対応のg番号配列を作る
                foreach($tmpGroupArray as $key => $groupData){
                    $keyToId[$key] = 'g' . $groupNum;
                    $groupNum++;
                }
                // カラムグループIDをg1,g2,g3...に変換
                foreach($tmpGroupArray as $key => $groupData){
                    foreach($columnGroupArray as $columnGroupData){
                        if($columnGroupData['COL_GROUP_ID'] == $key){
                            $parent = "";
                            if($columnGroupData['PA_COL_GROUP_ID'] != ""){
                                $parent = preg_replace('/\/' . $columnGroupData['COL_GROUP_NAME'] . '$/' , '' , $columnGroupData['FULL_COL_GROUP_NAME']);
                            }
                            $columns = array();
                            foreach($groupData as $column){
                                if(array_key_exists($column,$keyToId)){
                                    $columns[] = $keyToId[$column];
                                }
                                else{
                                    $columns[] = $column;
                                }
                            }
                            $returnGroupArray[$keyToId[$key]] = array(
                                "COL_GROUP_ID"   => $columnGroupData['COL_GROUP_ID'],
                                "COL_GROUP_NAME" => $columnGroupData['COL_GROUP_NAME'],
                                "PARENT" => $parent,
                                "COLUMNS" => $columns
                            
                            );
                            break;
                        }
                    }
                }
                
                $returnDataArray['group'] = $returnGroupArray;
                
                // 冒頭の項目配列を作成(i1,i2,g1,g2,r1とか)
                $columns = array();
                foreach($returnDataArray['item'] as $key => $item){
                    // 縦メニュー項目(repeat-item)の場合、r1を入る
                    if($convertFlag == true && in_array($key,$returnDataArray['repeat']['r1']['columns'])){
                        $columns[] = 'r1';
                    }
                    // 重複縦メニューの場合、スキップ
                    else if($item['REPEAT_ITEM'] === true){
                        continue;
                    }
                    // 親カラムがない項目の場合、項目を入る
                    else if($item['COL_GROUP_ID'] == ""){
                        $columns[] = $key;
                    }
                    // 親カラムがある項目の場合、ルート親カラムを入る
                    else{
                        $group = $keyToId[$item['COL_GROUP_ID']];
                        if($returnDataArray['group'][$group]["PARENT"] == ""){
                            $columns[] = $group;
                        }
                        else{
                            $parent = substr($returnDataArray['group'][$group]['PARENT'],0,strpos($returnDataArray['group'][$group]['PARENT'],'/'));
                            foreach($returnDataArray['group'] as $key => $group){
                                if($group['COL_GROUP_NAME'] == $parent){
                                    $columns[] = $key;
                                    break;
                                }
                            }
                        }
                    }
                }
                $returnDataArray['menu']['columns'] = array_values(array_unique($columns));
                
                
                $returnDataArray['menu']['number-item']  = count($returnDataArray['item']);
                $returnDataArray['menu']['number-group'] = count($returnDataArray['group']);
                
                
                $arrayResult = array("000", "",json_encode($returnDataArray));

                if($arrayResult[0]=="000"){
                    web_log( $g['objMTS']->getSomeMessage("ITAWDCH-STD-4001",__FUNCTION__));
                }else if(intval($arrayResult[0])<500){
                    web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4002",__FUNCTION__));
                }else{
                    web_log( $g['objMTS']->getSomeMessage("ITAWDCH-ERR-4001",__FUNCTION__));
                }

            }
            catch(Exception $e){
                return makeAjaxProxyResultStream($arrayResult);
            }
            return makeAjaxProxyResultStream($arrayResult);
        }

        //-- サイト個別PHP要素、ここまで--
    }
    $server = new HTML_AJAX_Server();
    $db_access = new Db_Access();
    $server->registerClass($db_access);
    $server->handleRequest();
?>
