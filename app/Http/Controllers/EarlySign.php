<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EarlySign extends Controller
{
    //
    private function _checkBind($openid){
        return !empty(\App\SignUser::find($openid));
    }
    private function _checkSign($openid){
        $user = \App\SignUser::find($openid);
        $lastTime = $user->last_sign || '';
        return !(date('ymd',$lastTime) == date('ymd'));
    }
    private function _userToArray($user){
        if(empty($user)){
            $res = array();
        }else {
            $res = array(
                'name' => $user->name,
                'score' => $user->score,
                'bindTime' => $user->bind_time,
                'lastSign' => $user->last_sign,
                'continueSign' => $user->continue_sign,
            );
        }
        return $res;
    }
    private function _scoreHandler($user){
        $score = 10;
        return $score;
    }
    private function _location($x, $y) {
        header("Content-type: text/html; charset=utf-8");
        $location = $y . "," . $x;
        $location = @file_get_contents("http://api.map.baidu.com/geoconv/v1/?ak=tTpnFO5fcIEjQrDGDmLWbxcM&coords=" . $location);
        $result = json_decode($location,true);
        $x = $result['result'][0]['x'];
        $y = $result['result'][0]['y'];
        $location = @file_get_contents("http://api.map.baidu.com/geocoder/v2/?ak=tTpnFO5fcIEjQrDGDmLWbxcM&output=json&pois=1&location=$y,$x");
        $result = json_decode($location,true);
        return [
            "address" => $result['result']['sematic_description'],
            "city" => $result['result']['addressComponent']['city']
        ];
    }

    public function checkSign($openid){
        $state = $this->_checkSign($openid)?'no':'ok';
        return response()->json(['state' => $state]);
    }
    public function checkBind($openid){
        $state = $this->_checkBind($openid)?'ok':'no';
        return response()->json(['state' => $state]);
    }
    /*
     *
     * CSUid
     * Name
     * NickName
     * openid
     * */
    public function Bind(Request $req){
        $user = new \App\SignUser;
        $user->openid = $req->input('openid');
        $user->schoolNum = $req->input('CSUid');
        $user->name = $req->input('NickName');
        $user->bind_time = time();
        if($user->save()) return response()->json(['result' => 'ok']);
        else return response()->json(['result' => 'no']);
    }

    public function queryById($timeFlag,$openid){
        switch ($timeFlag){
            case 'today':
                $user = DB::select('select * from `zt_zqqd_qd` where date(now()) = date(`time`) and `openid` = ?',$openid);
                //$user = \App\SignRecorder::where('openid',$openid)->where();
                //todo check the result
                return response()->json(empty($user)?[]:$user);
                break;
            case 'allday':
                $user = \App\SignUser::find($openid);
                return response()->json($this->_userToArray($user));
                break;
            default:
                return response()->json([]);
                break;
        }
    }
    public function queryMany($timeFlag,$num){
        switch ($timeFlag){
            case 'today':
                $user = DB::select('select * from `zt_zqqd_qd` where date(now()) = date(`time`) order by `score` desc limit 0,?',$num);
                return response()->json(empty($user)?[]:$user);
                break;
            case 'allday':
                $users = \App\SignUser::all()->orderBy('score','desc')->take($num)->get();
                $res = [];
                foreach ($users as $user){
                    array_push($res,$this->_userToArray($user));
                }
                return response()->json($res);
                break;
            default:
                return response()->json([]);
                break;
        }
    }
    /*
     * input:
     * openid,
     * lx,
     * ly,
     *
     * */
    public function sign(Request $req,$openid){
        $time = time();
        if(!$this->_checkBind($openid)){
            return response()->json(['state' => 'notBind']);
        }elseif(!$this->_checkSign($openid)){
            return response()->json(['state'=> 'hasSigned']);
        }elseif(!(strtotime('06:00:00') <= $time) or !($time <= strtotime('07:00:00'))){
            return response()->json(['state' => 'timeOut']);
        }else{
            $record = new \App\SignRecorder;
            $record->openid = $openid;
            $record->location = $this->_location($req->input('lx'),$req->input('ly'))['address'];
            $record->time = $time;
            $lastRank = \App\SignRecorder::where('time','>',date('ymd'))->orderBy('time','desc')->take(1)->get('rank');
            $lastRank = empty($lastRank)?0:$lastRank;
            $record->rank = $lastRank+1;
            $record->save();
            $user = \App\SignUser::find($openid);
            $user->last_sign = $time;
            $user->continue_sign ++;
            $user->score += $this->_scoreHandler($user);
            $user->save();
            return response()->json([
                'state' => 'ok',
                'time' => $time,
                'rank' => $lastRank+1
                ]);
        }
    }
}
