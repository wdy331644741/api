<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException as OmgException;
use App\Models\AppStartpage;
use App\Models\Banner;
use App\Models\ImgPosition;
use App\Service\Func;
use Illuminate\Pagination\Paginator;
use Lib\JsonRpcClient;

class BannerJsonRpc extends JsonRpc {

	/**
	 *  banner列表
	 *
	 * @JsonRpcMethod
	 */
	public function bannerList($params) {
		$where = array(
			'can_use' => 1,
		);
		$position = $params->position;
		$pageNum = isset($params->pageNum) ? $params->pageNum : 5;
		$page = isset($params->page) ? $params->page : 1;
		if (empty($position)) {
			throw new OmgException(OmgException::VALID_POSITION_FAIL);
		} else {
			$where['position'] = $position;
		}
		switch ($position) {
		// 发现页 不做时间限制
		case 'discover':
			$data1 = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')
				->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->get()->toArray();
			/*$data2 = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')
				->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->whereRaw('end < now()')
				->orderByRaw('sort DESC')->get()->toArray();*/
			$data = $data1;
			break;
		// 大事记 增加分页
		case 'memorabilia':
			Paginator::currentPageResolver(function () use ($page) {
				return $page;
			});

			$res = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->paginate($pageNum)->toArray();
			$data = $res['data'];
			$rData['total'] = $res['total'];
			$rData['per_page'] = $res['per_page'];
			$rData['current_page'] = $res['current_page'];
			$rData['last_page'] = $res['last_page'];
			$rData['from'] = $res['from'];
			$rData['to'] = $res['to'];
			break;
		// 移动端banner限制只显示前5张图
		case 'mobile':
			$data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->limit(7)->get()->toArray();
			$data = $this->addChannelImg($data, 'mobile');
			$data = $this->specialChannelImg($data, 'mobile');
//			$data = $this->specialChannelImg2($data, 'mobile');
			break;
		case "annualreport":
			Paginator::currentPageResolver(function () use ($page) {
				return $page;
			});
			$res = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->paginate($pageNum)->toArray();
			$data = $res['data'];
			$rData['total'] = $res['total'];
			$rData['per_page'] = $res['per_page'];
			$rData['current_page'] = $res['current_page'];
			$rData['last_page'] = $res['last_page'];
			$rData['from'] = $res['from'];
			$rData['to'] = $res['to'];
			break;
		case "annualreport_app":
			Paginator::currentPageResolver(function () use ($page) {
				return $page;
			});
			$res = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->paginate($pageNum)->toArray();
			$data = $res['data'];
			$rData['total'] = $res['total'];
			$rData['per_page'] = $res['per_page'];
			$rData['current_page'] = $res['current_page'];
			$rData['last_page'] = $res['last_page'];
			$rData['from'] = $res['from'];
			$rData['to'] = $res['to'];
			break;
		case "index_icon":
			if (empty($params->tag)) {
				throw new OmgException(OmgException::VALID_POSITION_FAIL);
			} else {
				$where['tag'] = $params->tag;
			}
			$data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'desc', 'tag', 'short_des', 'short_desc', 'created_at', 'updated_at', 'release_time')->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->get()->toArray();
			break;
			//在线客服-广告位
        case "ad":
        case "infolink":
            Paginator::currentPageResolver(function () use ($page) {
                return $page;
            });
            $res = BANNER::select( 'name','img_path', 'url')->where($where)
                ->where(function ($query) {
                    $query->whereNull('start')->orWhereRaw('start < now()');
                })
                ->where(function ($query) {
                    $query->whereNull('end')->orWhereRaw('end > now()');
                })
                ->orderByRaw('sort DESC')->paginate($pageNum)->toArray();
            $data = $res['data'];
            $rData['total'] = $res['total'];
            $rData['per_page'] = $res['per_page'];
            $rData['current_page'] = $res['current_page'];
            $rData['last_page'] = $res['last_page'];
            $rData['from'] = $res['from'];
            $rData['to'] = $res['to'];
            break;
        case "share_img":
            $data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url',"short_desc", 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')
                ->where($where)
                ->where(function ($query) {
                    $query->whereNull('start')->orWhereRaw('start < now()');
                })
                ->where(function ($query) {
                    $query->whereNull('end')->orWhereRaw('end > now()');
                })
                ->orderByRaw('sort DESC')->get()->toArray();
            break;
		// 默认
		default:
			$data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->get()->toArray();
			if ($position == 'pc') {
				$data = $this->addChannelImg($data, 'pc');
				$data = $this->specialChannelImg($data, 'pc');
			}
		}

		$rData['list'] = $data;
		$rData['Utag'] = md5(json_encode($data));
		$rData['Etag'] = isset($data[0]['release_time']) && !empty($data[0]['release_time']) ? $data[0]['release_time'] : '';
		return array(
			'code' => 0,
			'message' => 'success',
			'data' => $rData,
		);
	}

	/**
	 * 分享配置
	 *
	 * @JsonRpcMethod
	 */
	public function shareConfig($params) {
		$where = array(
			'can_use' => 1,
		);
		$position = $params->position;
		if (empty($position)) {
			throw new OmgException(OmgException::VALID_POSITION_FAIL);
		} else {
			$where['position'] = $position;
		}
		$data = BANNER::select('id', 'name', 'desc', 'short_desc', 'img_path', 'url', 'start', 'end', 'created_at', 'updated_at', 'release_time')
			->where($where)
			->where(function ($query) {
				$query->whereNull('end')->orWhereRaw('end > now()');
			})
			->orderByRaw('sort DESC')->first();

		if (!$data) {
			throw new OmgException(OmgException::NO_DATA);
		}

		$data['Etag'] = isset($data['release_time']) && !empty($data['release_time']) ? $data['release_time'] : '';
		return array(
			'code' => 0,
			'message' => 'success',
			'data' => $data,
		);
	}

	/**
	 * 渠道落地页
	 *
	 * @JsonRpcMethod
	 */
	public function bannerChannel($params) {
		if (!isset($params->channel)) {
			throw new OmgException(OmgException::VALID_POSITION_FAIL);
		}
		$where = array(
			'can_use' => 1,
			'position' => 'channel',
			'name' => strtolower($params->channel),
		);
		$data = BANNER::select('id', 'name', 'type', 'img_path', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
			->where(function ($query) {
				$query->whereNull('start')->orWhereRaw('start < now()');
			})
			->where(function ($query) {
				$query->whereNull('end')->orWhereRaw('end > now()');
			})
			->orderByRaw('sort DESC')->first();
		if (!$data) {
			$where = array(
				'can_use' => 1,
				'position' => 'channel',
				'name' => '',
			);
			$data = BANNER::select('id', 'name', 'type', 'img_path', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->first();
		}
		if (!$data) {
			throw new OmgException(OmgException::NO_DATA);
		}
		$data['Etag'] = $data['release_time'];
		return array(
			'code' => 0,
			'message' => 'success',
			'data' => $data,
		);
	}

	/**
	 * PC渠道落地页
	 *
	 * @JsonRpcMethod
	 */
	public function bannerPCChannel($params) {
		if (!isset($params->channel)) {
			throw new OmgException(OmgException::VALID_POSITION_FAIL);
		}
		$where = array(
			'can_use' => 1,
			'position' => 'pc_channel',
			'name' => strtolower($params->channel),
		);
		$data = BANNER::select('id', 'name', 'type', 'img_path', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
			->where(function ($query) {
				$query->whereNull('start')->orWhereRaw('start < now()');
			})
			->where(function ($query) {
				$query->whereNull('end')->orWhereRaw('end > now()');
			})
			->orderByRaw('sort DESC')->first();
		if (!$data) {
			$where = array(
				'can_use' => 1,
				'position' => 'pc_channel',
				'name' => '',
			);
			$data = BANNER::select('id', 'name', 'type', 'img_path', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->orderByRaw('sort DESC')->first();
		}
		if (!$data) {
			throw new OmgException(OmgException::NO_DATA);
		}
		$data['Etag'] = $data['release_time'];
		return array(
			'code' => 0,
			'message' => 'success',
			'data' => $data,
		);
	}

	/**
	 * 活动弹窗
	 *
	 * @JsonRpcMethod
	 */
	public function activityPop() {
		$where = array(
			'can_use' => 1,
			'position' => 'pop',
		);
		$data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'url_ios', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time', 'view_frequency')->where($where)
			->where(function ($query) {
				$query->whereNull('start')->orWhereRaw('start < now()');
			})
			->where(function ($query) {
				$query->whereNull('end')->orWhereRaw('end > now()');
			})
			->orderByRaw('sort DESC')->first();

		if (!$data) {
			throw new OmgException(OmgException::NO_DATA);
		}
		$data['Etag'] = $data['release_time'];
		return array(
			'code' => 0,
			'message' => 'success',
			'data' => $data,
		);
	}

	public function _getPostion($where = array()) {
		$list = ImgPosition::where($where)->get()->toArray();
		return $list;
	}
	/**
	 *  获取启动页
	 *
	 * @JsonRpcMethod
	 */
	public function appStartpages($params) {
		if (!isset($params->platform) || !isset($params->value)) {
			throw new OmgException(OmgException::PARAMS_NEED_ERROR);
		}
		$filter = [
			'platform' => $params->platform,
			'enable' => 1,
		];
		$newdate = date('Y-m-d H:i:s');
		$data = AppStartpage::select('id', 'img1', 'img2', 'img3', 'img4', 'img5', 'img6','img7','target_url', 'release_at', 'online_time', 'offline_time')
			->where($filter)
			->where('online_time', '<=', $newdate)
			->where('offline_time', '>=', $newdate)
			->orderByRaw("id + sort DESC")
			->first();
		if ($data) {
			$data['Etag'] = strval(strtotime($data['release_at']));
			$data['img'] = $data["img{$params->value}"];
		}
		return array(
			'code' => 0,
			'message' => 'success',
			'data' => $data,
		);
	}

	/**
	 *  获取存管弹框
	 *
	 * @JsonRpcMethod
	 */
	public function appDepositoryPop() {
		//获取绑卡状态
		$url = env('ACCOUNT_HTTP_URL');
		$client = new JsonRpcClient($url);
		$status = $client->neededActive();
		//成功
		if (isset($status['result']['code']) && $status['result']['code'] == 0) {
			if ($status['result']['active_status'] == 0) {
				$bindStatus = false;
			} elseif ($status['result']['active_status'] == 1) {
				$bindStatus = true;
			} else {
				return array(
					'code' => 0,
					'message' => 'success',
					'data' => []
				);
			}
		} else {
			throw new OmgException(OmgException::API_FAILED);
		}

		$where = array(
			'position' => 'pop',
		);
		if ($bindStatus) {
			$where['name'] = '存管弹窗立即激活';
		} else {
			$where['name'] = '存管弹窗立即开通';
		}
		$data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'url_ios', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
			->where(function ($query) {
				$query->whereNull('start')->orWhereRaw('start < now()');
			})
			->where(function ($query) {
				$query->whereNull('end')->orWhereRaw('end > now()');
			})
			->orderByRaw('sort DESC')->first();

		if (!$data) {
			throw new OmgException(OmgException::NO_DATA);
		}
		$data['Etag'] = $data['release_time'];
		return array(
			'code' => 0,
			'message' => 'success',
			'data' => $data,
		);
	}

    /**
     * 移动端锁定页
     *
     * @JsonRpcMethod
     */
    public function bannerMobilePop($params) {
        
        $version = $params->version;
        if (!$version) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $where = array(
            'can_use' => 1,
            'position' => 'mobile_pop',
            'short_desc'=>$version,
        );
        $data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'url_ios', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time', 'view_frequency')->where($where)
            ->where(function ($query) {
                $query->whereNull('start')->orWhereRaw('start < now()');
            })
            ->where(function ($query) {
                $query->whereNull('end')->orWhereRaw('end > now()');
            })
            ->orderByRaw('sort DESC')->first();

        if (!$data) {
            throw new OmgException(OmgException::NO_DATA);
        }
        $data['version'] = $version;
        $data['Etag'] = $data['release_time'];
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );
    }

    /**
     * 获取提现页图标
     *
     * @JsonRpcMethod
     */
    public function getPutForwardIcon(){
        $where['position'] = "put_forward_icon";
        $where['can_use'] = 1;
        $data = BANNER::select('id', 'name', 'img_path', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
            ->where(function ($query) {
                $query->whereNull('start')->orWhereRaw('start < now()');
            })
            ->where(function ($query) {
                $query->whereNull('end')->orWhereRaw('end > now()');
            })
            ->orderByRaw('sort DESC')->first();

        if(isset($data->id)){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data,
            );
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => [],
        );
    }

	//特定渠道添加图片
	private function addChannelImg($data, $position) {
		global $userId;
		$userInfo = Func::getUserBasicInfo($userId, true);
		$thisChannel = isset($userInfo['from_channel']) ? $userInfo['from_channel'] : '';
		if (empty($thisChannel) || empty($position)) {
			return $data;
		}
		$channel = [
			"ali",
			"APPStore",
			"baidu",
			"baidupz",
			"chuizi",
			"huawei",
			"lenovo",
			"m360",
			"mbaidupz",
			"meizu",
			"oppo",
			"qq",
			"sogou",
			"vivo",
			"wanglibao1",
			"xiaomi",
			"APPStorePlus",
			"qqplus",
			"qqplus1",
			"tcsc1",
			"tcsc2",
			"tcsc3",
			"tcsc4",
			"tcsc5",
			"duokai",
			"dkh5",
			"zgby",
			"spicy_wlb",
			"yihuys",

		];
		if (in_array($thisChannel, $channel)) {
			$where = ['position' => $position, 'can_use' => 0, 'name' => "特定渠道显示，请勿动，请勿上线"];
			$arr = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')
				->where($where)
				->where(function ($query) {
					$query->whereNull('start')->orWhereRaw('start < now()');
				})
				->where(function ($query) {
					$query->whereNull('end')->orWhereRaw('end > now()');
				})
				->take(1)->get()->toArray();
			if (empty($arr)) {
				return $data;
			}
			foreach ($data as $key => $item) {
				$arr[$key + 1] = $item;
			}
			return $arr;
		}
		return $data;
	}

    //特定渠道添加图片 2
    private function specialChannelImg($data, $position) {
        global $userId;
        $userInfo = Func::getUserBasicInfo($userId, true);
        $thisChannel = isset($userInfo['from_channel']) ? $userInfo['from_channel'] : '';
        if (empty($thisChannel) || empty($position)) {
            return $data;
        }
        $channel = [
            '360jj','360pcss','360ydss','APPStore','APPStorePlus','baidu','baidujj','baidupz','chuizi','fwh','huawei','lenovo','m360','m360jj','mbaidujj','mbaidupz','meizu','oppo','oppofeed','qq','qqplus','qqplus1','qqplus2','samsung','sgqqdh','sogou','sougou1','vivo','wanglibao1','xiaomi','xiaomiplus','ali','qqcpd','gdt','gdt1','sglccpt','360pz','m360pz','fhcpc','jrttcpc','sgpcss','sgydss','sgpz','msgpz','qqcpd1','ggkdg','wanglibao2','xlpx','fh1','fh2','fh3','fh4','fh5','bkl2018','toutiao1','toutiao2','toutiao3','toutiao4','toutiao5','toutiao6','toutiao7','toutiao8','toutiao9','toutiao10','toutiao11','toutiao12','toutiao13','toutiao14','toutiao15','toutiao16','toutiao17','toutiao18','toutiao19','toutiao20','LDLT','kbyg','kbjk'
        ];
        if (in_array($thisChannel, $channel)) {
            $where = ['position' => $position, 'can_use' => 0, 'name' => "特定渠道显示，快乐大本营送芒果月卡"];
            $arr = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')
                ->where($where)
                ->where(function ($query) {
                    $query->whereNull('start')->orWhereRaw('start < now()');
                })
                ->where(function ($query) {
                    $query->whereNull('end')->orWhereRaw('end > now()');
                })
                ->take(1)->get()->toArray();
            if (empty($arr)) {
                return $data;
            }
            foreach ($data as $key => $item) {
                $arr[$key + 1] = $item;
            }
            return $arr;
        }
        return $data;
    }

    /*
    private function specialChannelImg2($data, $position) {
        global $userId;
        $userInfo = Func::getUserBasicInfo($userId, true);
        $thisChannel = isset($userInfo['from_channel']) ? $userInfo['from_channel'] : '';
        if (empty($thisChannel) || empty($position)) {
            return $data;
        }
        $channel = [
            'haoyouyaoqing1','360jj','360pcss','360ydss','APPStore','APPStorePlus','baidu','baidujj','baidupz','chuizi','fwh','huawei','lenovo','m360','m360jj','mbaidujj','mbaidupz','meizu','oppo','oppofeed','qq','qqplus','qqplus1','qqplus2','samsung','sgqqdh','sogou','sougou1','vivo','wanglibao1','xiaomi','xiaomiplus','ali','qqcpd','gdt','gdt1','sglccpt','360pz','m360pz','fhcpc','jrttcpc','sgpcss','sgydss','sgpz','msgpz','qqcpd1','ggkdg','xlps','fh1','fh2','fh3','fh4','fh5',' 518TYHDpc','518TYHDh5',' LDLT-dx','bkl2018','toutiao1','toutiao2','toutiao3','toutiao4','toutiao5','toutiao6','toutiao7','toutiao8','toutiao9','toutiao10','toutiao11','toutiao12','toutiao13','toutiao14','toutiao15','toutiao16','toutiao17','toutiao18','toutiao19','toutiao20','LDLT','kbjk',
        ];
        if (in_array($thisChannel, $channel)) {
            $where = ['position' => $position, 'can_use' => 0, 'name' => "特定渠道显示，三国集卡"];
            $arr = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')
                ->where($where)
                ->where(function ($query) {
                    $query->whereNull('start')->orWhereRaw('start < now()');
                })
                ->where(function ($query) {
                    $query->whereNull('end')->orWhereRaw('end > now()');
                })
                ->take(1)->get()->toArray();
            if (empty($arr)) {
                return $data;
            }
            if (strtotime($userInfo['create_time']) < strtotime($arr[0]['start'])) {
                return $data;
            }
            foreach ($data as $key => $item) {
                $arr[$key + 1] = $item;
            }
            return $arr;
        }
        return $data;
    }
    */
}
