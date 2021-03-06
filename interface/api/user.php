<?php
/**
 * 用户相关接口文件
 */
use User\Model\UcenterMemberModel;
include 'base.php';
include dirname(__FILE__).'/../../Application/User/Conf/config.php';
class user extends base{
	public function __construct(){
		$this->user_model = new UcenterMemberModel();
	}
	
	/**
	 * 检查是否已登陆
	 */
	public function is_login($param){
		
	}
	
	/**
	 * 登陆
	 */
	public function login($param){
		if($param['phone'] && $param['password']){
		    $phone = trim($param['phone']);
		    $password = trim($param['password']);
		    //$result = $this->user_model->login($phone,$password,3);
		    $result = $this->check_login($phone,$password);
		    switch($result){
		        case -1:
		            $this->getResponse('', '301');
		            break;
		        case -2:
		            $this->getResponse('', '302');
		            break;
		        default:
		            $info = D('Member')->info($result,'uid,subbranch_id,score');
		            	
		            //登录时积分奖励
		            $uid = intval($info['uid']);
		            $login_info = M('ucenter_member')->where('id='.$uid)->find();
		            $login_times = $login_info['login_times'];
		            $current_time = date('Ymd',time());
		            $last_login_time = date('Ymd',$login_info['last_login_time']);
		            if($current_time != $last_login_time){  //当天未登陆过
		                $detl_time = $current_time-$last_login_time;
		                if($detl_time == 1){  //连续登陆
		                    if($login_times == 4){
		                        //+10
		                        $mem['login_times'] = 0;
		                        $mem['score'] = $info['score']+10;
		                    }else {
		                        //+1
		                        $mem['login_times'] = array('exp', '`login_times`+1');
		                        $mem['score'] = $info['score']+1;
		                    }
		                }else{  //非连续登陆
		                    $mem['login_times'] = 1;
		                    $mem['score'] = $info['score']+1;
		                }
		            }
		            $mem['last_login_time'] = $u_mem['last_login_time'] = time();
		            $mem['last_login_ip'] = $u_mem['last_login_ip'] = get_client_ip(1);
		            $mem['login'] = array('exp', '`login`+1');
		            M('ucenter_member')->where('id='.$uid)->save($u_mem);
		            M('member')->where('uid='.$uid)->save($mem);
		            unset($info['score']);
		            	
		            $this->getResponse($info, '0');
		    }
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 私有方法-验证登陆
	 */
	private function check_login($phone,$passwd){
	    $map['mobile'] = trim($phone);
	    $passwd = trim($passwd);
	    $info = M('ucenter_member')->where($map)->find();
	    if($info){
	        $u_passwd = $info['password'];
	        if(think_ucenter_md5($passwd, UC_AUTH_KEY) !== $u_passwd){
	            return -2;
	        }else{
	            return $info['id'];
	        }
	    }else{
	        return -1;
	    }
	}
	
	/**
	 * 用户个人信息
	 */
	public function info($param){
		if($param['uid']){
			$uid = intval($param['uid']);
			$field = 'a.uid,a.head,a.truename,b.phone,a.sex,a.job,a.subbranch_id';
			$info = M()->table(C('DB_PREFIX').'member a')->join(C('DB_PREFIX').'ucenter_member b on a.uid=b.uid')
			           ->field($field)->where('uid='.$uid)->find();
			           //echo M()->getLastSql();
			if($info){
				$info['sex'] = $info['sex'] == '0' ? '女' : '男';
				$info['head'] = $info['head'];
				$subbranch = D('Subbranch')->getSubbranchDetail($info['subbranch_id']);
				unset($info['subbranch_id']);
				$info['subbrabch_name'] = $subbranch['name'];
			}
			$this->getResponse($info?$info:'', '0');
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 学习记录
	 */
	public function study($param){
		if($param['uid']){
			$uid = intval($param['uid']);
			$map['a.uid'] = $uid;
			$field = 'a.course_id,a.status,b.title,b.type,b,a.update_time,b.course_ico,b.video_url,b.gold';
			$records = M()->table(C('DB_PREFIX').'course_record a')->join(C('DB_PREFIX').'course b on a.course_id=b.course_id')
			           ->field($field)->where($map)->order('a.update_time desc')->select();

			if($records){
				$status_map = array(1=>'未考试',2=>'未通过',3=>'已通过');
				foreach($records as $k=>$v){
					$records[$k]['status'] = $status_map[$v['status']];
					$type = M('course_type')->where('id='.$v['type'])->find();
					$records[$k]['type'] = $type['name'];
					$records[$k]['update_time'] = date('Y-m-d',$v['update_time']);
				}
			}
			$this->getResponse($records?$records:array(), '0');
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 我的关注
	 */
	public function focus($param){
		if($param['uid']){
			$uid = intval($param['uid']);
			$map['a.uid'] = $uid;
			
			$field = 'a.course_id,a.focus_time,b.title,b.type,b,b.course_ico,b.video_url';
			$list = M()->table(C('DB_PREFIX').'course_focus a')->join(C('DB_PREFIX').'course b on a.course_id=b.course_id')
			           ->field($field)->where($map)->order('a.focus_time desc')->select();
			           
			if($list){
				foreach($list as $k=>$v){
					$type = M('course_type')->where('id='.$v['type'])->find();
					$list[$k]['type'] = $type['name'];
					$list[$k]['focus_time'] = date('Y-m-d',$v['focus_time']);
				}
			}
			$this->getResponse($list?$list:array(), '0');
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 我的金币
	 */
	public function gold($param){
		
	}
	
	/**
	 * 我的积分
	 */
	public function score($param){
		
	}
	
	/**
	 * 修改密码
	 */
	public function update($param){
		if($param['old_password'] && $param['new_password'] && $param['uid']){
			$map['uid'] = intval($param['uid']);
			$old_password = trim($param['old_password']);
			$user_info = M('ucenter_member')->where($map)->find();
			if($user_info){
				if(think_ucenter_md5($old_password, UC_AUTH_KEY) !== $user_info['password']){
					$this->getResponse('', '303');
				}else{
					$save['password'] = think_ucenter_md5(trim($param['new_password']),UC_AUTH_KEY);
					$res = M('ucenter_member')->where($map)->save($save);
					$this->getResponse('', $res?'0':'304');
				}
			}else{
				$this->getResponse('', '301');
			}
		}else{
			$this->getResponse('', '999');
		}
	}
	
	/**
	 * 退出
	 */
	public function logout($param){
		
	}
}