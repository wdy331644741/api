<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | such as the size rules. Feel free to tweak each of these messages.
    |
    */

    'accepted'             => ':attribute 必须接受。',
    'active_url'           => ':attribute 不是一个有效的网址。',
    'after'                => ':attribute 必须是一个在 :date 之后的日期。',
    'alpha'                => ':attribute 只能由字母组成。',
    'alpha_dash'           => ':attribute 只能由字母、数字和斜杠组成。',
    'alpha_num'            => ':attribute 只能由字母和数字组成。',
    'array'                => ':attribute 必须是一个数组。',
    'before'               => ':attribute 必须是一个在 :date 之前的日期。',
    'between'              => [
        'numeric' => ':attribute 必须介于 :min - :max 之间。',
        'file'    => ':attribute 必须介于 :min - :max kb 之间。',
        'string'  => ':attribute 必须介于 :min - :max 个字符之间。',
        'array'   => ':attribute 必须只有 :min - :max 个单元。',
    ],
    'boolean'              => ':attribute 必须为布尔值。',
    'confirmed'            => ':attribute 两次输入不一致。',
    'date'                 => ':attribute 不是一个有效的日期。',
    'date_format'          => ':attribute 的格式必须为 :format。',
    'different'            => ':attribute 和 :other 必须不同。',
    'digits'               => ':attribute 必须是 :digits 位的数字。',
    'digits_between'       => ':attribute 必须是介于 :min 和 :max 位的数字。',
    'distinct'             => ':attribute 已經存在。',
    'email'                => ':attribute 不是一个合法的邮箱。',
    'exists'               => ':attribute 不存在。',
    'filled'               => ':attribute 不能为空。',
    'image'                => ':attribute 必须是图片。',
    'in'                   => '已选的属性 :attribute 非法。',
    'in_array'             => ':attribute 没有在 :other 中。',
    'integer'              => ':attribute 必须是整数。',
    'ip'                   => ':attribute 必须是有效的 IP 地址。',
    'json'                 => ':attribute 必须是正确的 JSON 格式。',
    'max'                  => [
        'numeric' => ':attribute 不能大于 :max。',
        'file'    => ':attribute 不能大于 :max kb。',
        'string'  => ':attribute 不能大于 :max 个字符。',
        'array'   => ':attribute 最多只有 :max 个单元。',
    ],
    'mimes'                => ':attribute 必须是一个 :values 类型的文件。',
    'min'                  => [
        'numeric' => ':attribute 必须大于:min。',/*等于*/
        'file'    => ':attribute 大小不能小于 :min kb。',
        'string'  => ':attribute 至少为 :min 个字符。',
        'array'   => ':attribute 至少有 :min 个单元。',
    ],
    'not_in'               => '已选的属性 :attribute 非法。',
    'numeric'              => ':attribute 必须是一个数字。',
    'present'              => ':attribute 必须存在。',
    'regex'                => ':attribute 格式不正确。',
    'required'             => ':attribute 不能为空。',
    'required_if'          => '当 :other 为 :value 时 :attribute 不能为空。',
    'required_unless'      => '当 :other 不为 :value 时 :attribute 不能为空。',
    'required_with'        => '当 :values 存在时 :attribute 不能为空。',
    'required_with_all'    => '当 :values 存在时 :attribute 不能为空。',
    'required_without'     => '当 :values 不存在时 :attribute 不能为空。',
    'required_without_all' => '当 :values 都不存在时 :attribute 不能为空。',
    'same'                 => ':attribute 和 :other 必须相同。',
    'size'                 => [
        'numeric' => ':attribute 大小必须为 :size。',
        'file'    => ':attribute 大小必须为 :size kb。',
        'string'  => ':attribute 必须是 :size 个字符。',
        'array'   => ':attribute 必须为 :size 个单元。',
    ],
    'string'               => ':attribute 必须是一个字符串。',
    'timezone'             => ':attribute 必须是一个合法的时区值。',
    'unique'               => ':attribute 已经存在。',
    'url'                  => ':attribute 格式不正确。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention 'attribute.rule' to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom'               => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of 'email'. This simply helps us make messages a little cleaner.
    |
    */

    'attributes'           => [
        'name'                  => '名称',
        'username'              => '用户名',
        'email'                 => '邮箱',
        'first_name'            => '名',
        'last_name'             => '姓',
        'password'              => '密码',
        'password_confirmation' => '确认密码',
        'city'                  => '城市',
        'country'               => '国家',
        'address'               => '地址',
        'phone'                 => '电话',
        'mobile'                => '手机',
        'age'                   => '年轻',
        'sex'                   => '性别',
        'gender'                => '性别',
        'day'                   => '天',
        'month'                 => '月',
        'year'                  => '年',
        'hour'                  => '时',
        'minute'                => '分',
        'second'                => '秒',
        'title'                 => '标题',
        'content'               => '内容',
        'description'           => '描述',
        'excerpt'               => '摘要',
        'date'                  => '日期',
        'time'                  => '时间',
        'available'             => '可用的',
        'size'                  => '大小',
        'alias_name'            => '别名',
        'start_at'              => '开始时间',
        'end_at'                => '结束时间',
        'trigger_type'          => '触发类型',
        'min_time'              => '开始时间',
        'max_time'              => '结束时间',
        'activity_id'           => '活动ID',
        'channels'              => '渠道',
        'type_id'               => '类型',
        'trigger_index'         => '触发优先级',
        'group_id'              => '组活动',
        'pre'                   => '渠道组',
        'cover'                 => '封面',
        'source'                => '来源',
        'min_recharge'          => '最小充值金额',
        'max_recharge'          => '最大充值金额',
        'min_cast'              => '最小投资金额',
        'max_cast'              => '最大投资金额',
        'frequency'             => '频次限制',
        'number'                => '数量',
        'award_type'            => '奖品类型',
        'award_id'              => '奖品id',
        'expire_time'           => '过期时间',
        'rate_increases'        => '加息值',
        'rate_increases_type'   => '加息时长类型',
        'rate_increases_day'    => '加息时长天数',
        'rate_increases_start'  => '加息时长开始时间',
        'rate_increases_end'    => '加息时长结束时间',
        'effective_time_type'   => '有效时间类型',
        'effective_time_day'    => '有效时间顺延天数',
        'effective_time_start'  => '有效时间开始时间',
        'effective_time_end'    => '有效时间结束时间',
        'investment_threshold'  => '投资门槛',
        'project_duration_type' => '项目期限类型',
        'red_type'              => '红包类型',
        'red_money'             => '红包金额',
        'red_max_money'         => '红包最高金额',
        'percentage'            => '红包百分比值',
        'experience_amount_money'=> '固定金额',
        'experience_amount_multiple'=> '投资额倍数',
        'desc'                  => '简介',
        'coupon_id'             => '优惠券ID',
        'position'              => '位置',
        'img_path'              => '图片地址',
        'activity_time'         => '图片活动的时间',
        'id'                    => '操作ID',
        'update_time'           => '更新时间',
        'platform'              => '平台',
        'url'                   => '链接',
        'force'                 => '强制开启选项',
        'version'               => '版本号',
        'award_rule'            => '发奖几率',
        'isfirst'               => '是否首次',
        'start_time'            => '开始时间',
        'end_time'              => '结束时间',
        'min_recharge_all'      => '最小充值总金额',
        'max_recharge_all'      => '最大充值总金额',
        'min_cast_all'          => '最小投资总金额',
        'max_cast_all'          => '最大投资总金额',
        'is_invite'             => '是否被邀请',
        'min_invitenum'         => '最小邀请人数',
        'max_invitenum'         => '最大邀请人数',
        'min_level'             => '最小用户等级',
        'max_level'             => '最大用户等级',
        'min_credit'            => '最小用户积分',
        'max_credit'            => '最大用户积分',
        'min_balance'           => '最小用户余额',
        'max_balance'           => '最大用户余额',
        'min_payment'           => '最小用户回款',
        'max_payment'           => '最大用户回款',
        'structure'             => '构建号',
        'source_name'           => '来源名',
        'coop_status'           => '合作状态',
        'classification'        => '渠道分类',
        'is_abandoned'          => '是否废弃',
        'join_max'              => '参与人数上限',
        'contents'              => '内容',
        'integral'              => '积分值'
    ],

];
