<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>月利宝</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="baidu-site-verification" content="iBKXouRC1a"/>
    <link type="text/css" rel="styleSheet" href='{!! env("STYLE_BASE_URL") !!}/css/public.css'>
    <link type="text/css" rel="styleSheet" href='{!! env("STYLE_BASE_URL") !!}/css/help.css'>
    <link href="https://php1.wanglibao.com/css/header-footer.css" rel="stylesheet" type="text/css"/>
    <link href="https://php1.wanglibao.com/css/ylb.css?v=@version@" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="sheader"></div>
<div class="container help">
    <div class="space-vertical-m"></div>
    <div class="help-menu">
        <ul>
            @foreach($types as $type)
            <li data-target="topic_{!! $type->id !!}" class=""><a href="#">{!! $type->name !!}</a></li>
        </ul>
    </div>
    <div class='help-content'>
        <div data-source="topic_0" class="hot-items help-box">
            <h1>常见问题</h1>
            <ul>
                <li><a href="#" data-topic="topic_4" data-item="content_64">平台提现手续费是怎么收取的？</a>
                </li>
                <li class="odd"><a href="#" data-topic="topic_2" data-item="content_63">用户解绑银行卡需要提供什么？</a>
                </li>
                <li><a href="#" data-topic="topic_4" data-item="content_103">如何绑定银行卡？</a>
                </li>
                <li class="odd"><a href="#" data-topic="topic_4" data-item="content_104">什么是同卡进出？</a>
                </li>
                <li><a href="#" data-topic="topic_4" data-item="content_61">平台充值提现须知</a>
                </li>
                <li class="odd"><a href="#" data-topic="topic_6" data-item="content_1">什么是网利宝?</a>
                </li>
                <li><a href="#" data-topic="topic_6" data-item="content_2">网利宝的优势是什么?</a>
                </li>
                <li class="odd"><a href="#" data-topic="topic_6" data-item="content_3">网利宝的投资方是谁?</a>
                </li>
                <li><a href="#" data-topic="topic_5" data-item="content_8">如何修改密码？</a>
                </li>
                <li class="odd"><a href="#" data-topic="topic_3" data-item="content_17">如果出现项目未满标的情况，投资失败怎么办</a>
                </li>
                <li><a href="#" data-topic="topic_7" data-item="content_52">红包/加息券使用规则？</a>
                </li>
                <li class="odd"><a href="#" data-topic="topic_4" data-item="content_105">提现限额是多少？</a>
                </li>
            </ul>
        </div>
        <div data-source="topic_9" class="list-items help-box">
            <h1>会员体系</h1>
            <div class="list-container">
                <div data-source="content_106" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何由新手变为普通会员？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">通过实名认证并投资满1000元可成为网利宝普通会员。</p></div>
                </div>
                <div data-source="content_107" class="list-item">
                    <div class="list-item-title"><span class="anchor">待收本息满足升级标准是否可以立即成为VIP？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">定期产品的待收本息，满足升级标准后，将于达到标准之后的次日升级。</p></div>
                </div>
                <div data-source="content_110" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是会员红包及红包的方式？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">会员红包：网利宝赠送给会员的现金红包在现有级别有效期内每月1日，按月等份发给用户；</p>

                        <p align="left">用户在上月完成升级的，本月1日收到的将是升级后的红包；红包不叠加，升级后将不会收到升级前的红包。</p>

                        <p align="left">例如：V3会员的会员红包是600元（6个月*100元），4月1日会收到100元现金红包，如果TA在4月30日时投资了40万升级到SVIP，5月1日将收到200元的现金红包；如果TA在1日当天才投的40万，那么收到的还是V3对应的100元现金红包。</p>
                    </div>
                </div>
                <div data-source="content_111" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是会员加息券及加息券的发放？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">每月加息券：网利宝每月送给VIP和SVIP的专享加息券，以发送当时会员所处的级别为准，每月一张，不定时发送；级别越高，额度越大。</p>

                        <p align="left">例如： V1会员在4月9日当天投资10万，次日升级为V3会员，则会在9日获得V1会员的0.5%专享加息券一张。</p>
                    </div>
                </div>
                <div data-source="content_112" class="list-item">
                    <div class="list-item-title"><span class="anchor">会员升级后会收到多少的会员红包？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">会员有效期内的每月1日，系统自动发送对应的会员级别红包；用户在1日前升级的，收到的将是升级后的红包，红包不能叠加，升级后将不会收到升级前的红包。</p>

                        <p align="left">例如：V3会员的会员红包是600元，4月1日收到100元现金红包，如果在4月30日时投资了40万升级到SVIP，5月1日将收到200元的现金红包；如果在1日当天才投的40万，那么收到的还是V3对应的100元现金红包。</p>
                    </div>
                </div>
                <div data-source="content_113" class="list-item">
                    <div class="list-item-title"><span class="anchor">礼包惊喜是什么？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">VIP和SVIP将会不定期收到网利宝提供的专属投资礼包，级别越高，礼包越大，惊喜多多，福利多多。</p>
                    </div>
                </div>
            </div>
        </div>
        <div data-source="topic_8" class="list-items help-box">
            <h1>月利宝/债权转让</h1>
            <div class="list-container">
                <div data-source="content_82" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是月利宝理财计划？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">月利宝理财计划是网利宝平台推出的快捷方便、退出灵活的优选理财计划。该计划包含多种期限模式，每种期限的计划包含对应的锁定期，采用智能匹配债权，可随时发起债权转让，其收益率会随持有时间的增加有一定程度的提升，并享受当日计息，期限结束后，投资资金将自动退出月利宝，转至投资用户的网利宝账户。</span></p>
                    </div>
                </div>
                <div data-source="content_83" class="list-item">
                    <div class="list-item-title"><span class="anchor">月利宝理财计划安全吗？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">月利宝所有产品适用于平台的各项风控手段。</span></p>
                    </div>
                </div>
                <div data-source="content_84" class="list-item">
                    <div class="list-item-title"><span class="anchor">月利宝理财计划有什么优势？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">（1）收益递增，每个月收益率会随持有时间增加而提升；</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">（2）随时退出，投资用户可在月利宝计划的投资期限内申请债权转让，以实现提前退出；</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">（3）当日计息，投资产品在购买日即开始计息（购买日到实际计息日所得利息将于投资计划的第一个还款日统一发放）。</span></p>
                    </div>
                </div>
                <div data-source="content_85" class="list-item">
                    <div class="list-item-title"><span class="anchor">月利宝理财计划分为哪几类？收益是怎样的？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p style="line-height: 20.7999992370605px;">月利宝分为四大类（以下利率仅供参考，具体以实际利率为准）：</p>

                        <table border="0" cellpadding="0" cellspacing="0" style="width:552px;" width="552">
                            <colgroup>
                                <col style="text-align: center;">
                                <col style="text-align: center;">
                                <col style="text-align: center;">
                                <col style="text-align: center;">
                                <col style="text-align: center;">
                                <col style="text-align: center;">
                            </colgroup>
                            <tbody>
                            <tr height="19">
                                <td height="19" style="height: 19px; width: 87px; text-align: center;">名称</td>
                                <td style="width: 91px; text-align: center;">项目总期限</td>
                                <td style="width: 89px; text-align: center;">锁定期限</td>
                                <td style="width: 92px; text-align: center;">基准年化利率</td>
                                <td style="width: 99px; text-align: center;">每月上浮利率</td>
                                <td style="width: 95px; text-align: center;">封顶年化利率</td>
                            </tr>
                            <tr height="19">
                                <td height="19" style="height: 19px; width: 87px; text-align: center;">月利宝A</td>
                                <td style="width: 91px; text-align: center;">1个月</td>
                                <td style="width: 89px; text-align: center;">7天</td>
                                <td style="width: 92px; text-align: center;">7.5%</td>
                                <td style="width: 99px; text-align: center;">—</td>
                                <td style="width: 95px; text-align: center;">7.5%</td>
                            </tr>
                            <tr height="19">
                                <td height="19" style="height: 19px; width: 87px; text-align: center;">月利宝B</td>
                                <td style="width: 91px; text-align: center;">3个月</td>
                                <td style="width: 89px; text-align: center;">1个月</td>
                                <td style="width: 92px; text-align: center;">8%</td>
                                <td style="width: 99px; text-align: center;">0.50%</td>
                                <td style="width: 95px; text-align: center;">9%</td>
                            </tr>
                            <tr height="19">
                                <td height="19" style="height: 19px; width: 87px; text-align: center;">月利宝C</td>
                                <td style="width: 91px; text-align: center;">6个月</td>
                                <td style="width: 89px; text-align: center;">3个月</td>
                                <td style="width: 92px; text-align: center;">8.5%</td>
                                <td style="width: 99px; text-align: center;">0.50%</td>
                                <td style="width: 95px; text-align: center;">11%</td>
                            </tr>
                            <tr height="19">
                                <td height="19" style="height: 19px; width: 87px; text-align: center;">月利宝D</td>
                                <td style="width: 91px; text-align: center;">12个月</td>
                                <td style="width: 89px; text-align: center;">6个月</td>
                                <td style="width: 92px; text-align: center;">10.25%</td>
                                <td style="width: 99px; text-align: center;">0.25%</td>
                                <td style="width: 95px; text-align: center;">13%</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div data-source="content_86" class="list-item">
                    <div class="list-item-title"><span class="anchor">月利宝理财计划有投资额度限制吗？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">月利宝起投金额为1000元，投资额需1000元的整数倍，无投资上限。若项目可投资金额少于1000元时，需全额购买。</span></p>
                    </div>
                </div>
                <div data-source="content_87" class="list-item">
                    <div class="list-item-title"><span class="anchor">月利宝理财计划中项目的加息与散标项目加息的区别是什么？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">散标项目平台加息中，平台加息部分在计息之日起，一次性返还至用户账户中；月利宝项目平台加息部分根据不同还款方式按照投资用户的还款计划返还给用户。</span></p>
                    </div>
                </div>
                <div data-source="content_88" class="list-item">
                    <div class="list-item-title"><span class="anchor">月利宝理财计划的计息方式及还款方式是什么？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">月利宝理财计划的计息方式为按月计息。</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">还款方式为：按月付息到期还本或一次性还本付息。</span></p>
                    </div>
                </div>
                <div data-source="content_89" class="list-item">
                    <div class="list-item-title"><span class="anchor">投资月利宝理财计划是否可以使用红包和加息券？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">目前，投资月利宝理财计划时支持使用红包、加息券，投资按次进行使用。</span></p>
                    </div>
                </div>
                <div data-source="content_90" class="list-item">
                    <div class="list-item-title"><span class="anchor">月利宝理财计划锁定期是什么意思？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">月利宝计划满额并通过审核后，月利宝进入锁定期。不同期限的月利宝的锁定期有所不同。在锁定期内转让统一收取手续费。月利</span><span style="line-height: 1.6;">宝项目债权转让后，没有锁定期的概念。</span></p>
                    </div>
                </div>
                <div data-source="content_92" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是债权转让？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">债权转让，指投资人将在网利宝平台投资的借款项目转让给其他投资人（即受让人），并与受让人签订债权转让协议的操作。债权</span><span style="line-height: 1.6;">转让能提高投资人的资金流动性。</span></p>
                    </div>
                </div>
                <div data-source="content_93" class="list-item">
                    <div class="list-item-title"><span class="anchor">哪些项目可以申请债权转让？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">目前，只有月利宝和转让标可以进行债权转让。</span></p>
                    </div>
                </div>
                <div data-source="content_94" class="list-item">
                    <div class="list-item-title"><span class="anchor">债权转让的作用是什么？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">债权转让能提高投资人的资金流动性，当投资人有紧急资金需求时，可转让自己购买的、符合相应条件的债权给其他投资人，获得</span><span style="line-height: 1.6;">流动资金。</span></p>
                    </div>
                </div>
                <div data-source="content_95" class="list-item">
                    <div class="list-item-title"><span class="anchor">购买转让债权后可以再转让吗？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">凡是符合转让规则的债权转让项目都可以在购买后再次被转让。</span></p>
                    </div>
                </div>
                <div data-source="content_96" class="list-item">
                    <div class="list-item-title"><span class="anchor">月利宝项目进行债权转让时，怎么收费？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p style="line-height: 20.7999992370605px;">（1）提前退出费用：若在锁定期内申请提前退出，需支付转让债权本金部分的2%作为提前退出费用；若在锁定期过后申请提前退出<span style="line-height: 1.6;">，不收取提前退出费用。</span></p>

                        <p style="line-height: 20.7999992370605px;"><span style="line-height: 1.6;">（2）再转让手续费：债权受让人购买月利宝债权转让标后再次进行债权转让的，不存在锁定期的概念，所以不收取提前退出费用，</span><span style="line-height: 1.6;">但应向网利宝平台支付转让债权本金的3‰作为再转让手续费。</span></p>
                    </div>
                </div>
                <div data-source="content_97" class="list-item">
                    <div class="list-item-title"><span class="anchor">债权转让过程中有哪些限制条件？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p style="line-height: 20.7999992370605px;">（1）结息日前72小时不能进行债权转让；<span style="line-height: 1.6;">&nbsp;</span></p>

                        <p style="line-height: 20.7999992370605px;">（2）逾期债权不能进行债权转让；&nbsp;</p>

                        <p style="line-height: 20.7999992370605px;">（3）债权转让人不能购买自己的债权；</p>

                        <p style="line-height: 20.7999992370605px;">（4）受让人购买债权转让项目，不参与全民淘金活动；</p>

                        <p style="line-height: 20.7999992370605px;"><span style="line-height: 1.6;">（5）债权转让均以本金为基准，转让时通过转让本金来智能匹配转让债权，购买时也是通过认购本金进行购买；</span></p>

                        <p style="line-height: 20.7999992370605px;"><span style="line-height: 1.6;">（6）网利宝平台要求的其他条件。</span></p>
                    </div>
                </div>
                <div data-source="content_98" class="list-item">
                    <div class="list-item-title"><span class="anchor">债权转让的时效为多久？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">债权转让的有效时间为债权转让人提出转让申请时起的3个自然日。超过转让有效期后，仍然没有成功转让的债权，视为转让失败，</span><span style="line-height: 1.6;">系统自动撤销该部分债权转让申请，债权回到债权转让人的网利宝账户中；已成功转出的部分仍然有效。</span></p>
                    </div>
                </div>
                <div data-source="content_99" class="list-item">
                    <div class="list-item-title"><span class="anchor">债权转让过程中，债权受让人能用红包和加息券吗？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">债权受让人在购买转让债权时不可使用红包、加息券。</span></p>
                    </div>
                </div>
                <div data-source="content_100" class="list-item">
                    <div class="list-item-title"><span class="anchor">提出债权转让申请后可以撤销转让吗？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="line-height: 20.7999992370605px;">在转让标售卖期间，债权转让人有权手动撤销该转让项目。</span></p>
                    </div>
                </div>
                <div data-source="content_101" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是债权转让溢价费？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p style="line-height: 20.7999992370605px;">当债权转让的成交价格超过转让债权当日公允价值时，债权转让人应向网利宝平台支付溢价金额的20%作为溢价费。溢价费在当次转<span style="line-height: 1.6;">让完成后即时扣除。由于目前为新品推广期，债权转让免收溢价费。</span></p>

                        <p style="line-height: 20.7999992370605px;">（公允价值：指截止转让当日该债权的实际价值，包括债权原值和当月未结清的利息两部分。）</p>
                    </div>
                </div>
                <div data-source="content_102" class="list-item">
                    <div class="list-item-title"><span class="anchor">投资月利宝理财计划，是否收取服务费？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">目前，投资月利宝项目不收取服务费。</p>
                    </div>
                </div>
            </div>
        </div>
        <div data-source="topic_7" class="list-items help-box">
            <h1>理财券/体验金</h1>
            <div class="list-container">
                <div data-source="content_50" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是加息券？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">在项目投资时选择使用，使用后，将在基本收益的基础上，产生额外的收益。</p>
                    </div>
                </div>
                <div data-source="content_51" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是红包？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left">在项目投资时选择使用，使用后，直接抵扣相应本金。</p>
                    </div>
                </div>
                <div data-source="content_52" class="list-item">
                    <div class="list-item-title"><span class="anchor">红包/加息券使用规则？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left"><span style="line-height: 20.7999992370605px;">散标：</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">同一个投资项目只能使用一种理财券（红包或者加息券），其中，加息券针对整个投资项目进行加息，红包针对每笔投资进行抵扣。</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">月利宝：&nbsp;</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">理财券（红包或者加息券）按次使用，加息券或红包针对每笔投资进行加息或者抵扣，单次投资只能选择一张理财券。</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">例如：</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">（1）用户在投资项目Ａ时，首笔投资选择使用０.４％加息券，</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">散标：用户后续针对该项目Ａ的每笔投资，均默认加息０.４％，不能再选择其它加息券和红包；</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">月利宝：用户针对该项目Ａ的该笔投资加息０.４％，后续再投资该项目需选择使用其他红包或者加息券；</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">（2）用户在投资项目Ａ时，首笔投资选择使用红包，</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">散标：用户后续针对项目Ａ的每笔投资，可选择继续使用其他已有红包，但不能再选择使用加息券。</span><br style="line-height: 20.7999992370605px;">
                            <span style="line-height: 20.7999992370605px;">月利宝：后续再投资该项目需选择使用其他红包或者加息券。</span></p>
                    </div>
                </div>
                <div data-source="content_78" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何领取体验金？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><div class="explain_content clearfix">
                            <p>新用户通过注册（老用户通过参与平台相关活动）获得体验金后，体验金自动放入相应账户并发送站内信通知。</p>
                        </div>

                        <p>&nbsp;</p>
                    </div>
                </div>
                <div data-source="content_79" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何使用体验金？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content">
                        <ul>
                            <li class="clearfix">
                                <div class="explain_content">
                                    <p>1、体验金只能在相关活动页投资体验标，投资时不能使用其它理财券。体验金有效期自发放日起15天内有效。</p>

                                    <p>2、体验金需要用户手动投资，投资时系统将自动按体验金总额一次性投资完毕。例如，用户A注册获得10000元体验金，未使用，之后该用户通过参与活动获得5000元体验金，此时用户选择投资体验标时（两笔体验金均在有效期内），系统默认将15000元体验金全部投资。</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div data-source="content_80" class="list-item">
                    <div class="list-item-title"><span class="anchor">体验金是否可提现？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content">
                        <ul>
                            <li class="clearfix">
                                <div class="explain_content">
                                    <p>1、体验金投资项目，17点前投资当日计息，17点后投资次日计息，项目到期日17点回款。还款本金系统自动收回，利息收益自动以余额形式发放至投资人的理财专区账户。例如，新手28888元体验金，计息日为1天（含节假日），今天16：50分投资，项目立即计息，今天17：00体验金投资收益会发放到您个人账户。</p>

                                    <p>2、体验金利息收益与正常账户余额一样，可用来投资和提现。</p>

                                    <p class="mt40">部分合作渠道来源用户无法享受此活动奖励，网利宝对此活动享有最终解释权。</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div data-source="topic_6" class="list-items help-box">
            <h1>认识网利宝</h1>
            <div class="list-container">
                <div data-source="content_1" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是网利宝?</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>网利宝（www.wanglibao.com）是网利金融旗下的在线理财平台。网利宝在业内创立产融结合的业务模式，凭借丰富的金融行业经验、互联网优势，联合中国领先的金融机构、产业龙头企业进行战略合作，为投资用户提供多元化、低风险、快捷方便、优质灵活的理财产品。网利宝的金融产品研发团队目前已覆盖全国20多个省市，通过严格的贷前审核、贷后跟踪、抵押、质押或保证担保等多重专业的风控手段，有效管控和防范风险，为投资者的财富增值保驾护航。</p>
                    </div>
                </div>
                <div data-source="content_2" class="list-item">
                    <div class="list-item-title"><span class="anchor">网利宝的优势是什么?</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>（1）低门槛、好收益：100元即可加入理财计划，平均预期年化收益高达13%。</p>

                        <p>（2）期限灵活：期限灵活多样，网利宝为用户提供短期、中期、长期三种不同期限的理财计划，满足用户资金的不同需求。</p>

                        <p>（3）专业权威：网利宝的核心团队成员来自高盛、摩根斯坦利、美银美林、淡马锡、中金、华夏基金等国内外著名金融机构，拥有丰富的金融从业经验。</p>
                    </div>
                </div>
                <div data-source="content_3" class="list-item">
                    <div class="list-item-title"><span class="anchor">网利宝的投资方是谁?</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>2014年2月获得了IDG资本A轮融资，IDG资本在中国大陆还投资了百度、腾讯、小米、携程网等知名企业；</p>

                        <p><span style="line-height: 1.6;">2015年5月获得了A股上市公司鸿利智汇（原鸿利光电）B轮融资，鸿利智汇是国家高新技术企业，鸿利智汇的LED器件在显指、光效、稳定性等方面的指标均达到国内领先水平，是国内率先通过美国环保总署(EPA)认可的第三方IES LM-80测试的LED封装企业，是中国白光LED器件领军者，致力打造中国领先的LED+互联网金融+车联网的生态平台。</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div data-source="topic_5" class="list-items help-box">
            <h1>注册/登录</h1>
            <div class="list-container">
                <div data-source="content_12" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何实名认证？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>若您还没有实名认证，可以在“我的账户”页面进行实名认证。。</p>
                    </div>
                </div>
                <div data-source="content_5" class="list-item">
                    <div class="list-item-title"><span class="anchor">为什么要实名认证？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>根据国家金融监管机构要求，为保障投资人的资金安全和合法权益，投资人需要在首次投资前输入真实姓名和身份证号码进行实名认证。</p>
                    </div>
                </div>
                <div data-source="content_6" class="list-item">
                    <div class="list-item-title"><span class="anchor">为什么手机收不到验证码？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content">
                        <p>（1） 请确认手机是否安装短信拦截或过滤软件；<br>
                            （2） 请确认手机是否能够正常接收短信（信号问题、欠费、停机等）；<br>
                            （3） 曾经发送过TD(退订短信)，请联系客服400-8588-066寻求帮助；<br>
                            （4） 短信收发过程中可能会存在延迟，请耐心等待。
                        </p>
                    </div>
                </div>
                <div data-source="content_7" class="list-item">
                    <div class="list-item-title"><span class="anchor">绑定银行卡失败要怎么处理？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>您的姓名、银行卡、身份证号需保持一致，否则会导致绑定银行卡失败。如果您需要变更银行卡信息，请提供您的身份证复印件、注册手机号、现有银行卡信息、新银行卡信息，发送至网利宝官方客服邮箱：kefu@wanglibank.com， 我们将在2个工作日内给您答复。</p>
                    </div>
                </div>
                <div data-source="content_8" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何修改密码？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>（1）点击屏幕右上角“我的账户”进入 （2）在左边菜单导航处，点击修改密码 （3）填写原始密码，重新设定密码即可修改成功</p>
                    </div>
                </div>
                <div data-source="content_65" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何成为网利宝的理财人？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>年满18周岁，具有完全民事权利能力和民事行为能力，可以在网利宝平台上进行注册、完成实名认证、绑定银行卡，成为理财人。</p>
                    </div>
                </div>
                <div data-source="content_66" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何修改绑定手机号？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>（1）点击网页版屏幕右上角【我的账户】进入（2）在【会员信息】—【账户安全】页面中可通过短信认证和人工审核两种方式修改绑定手机号。</p>
                    </div>
                </div>
                <div data-source="content_67" class="list-item">
                    <div class="list-item-title"><span class="anchor">身份信息正确，实名认证未通过怎么办？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>可能是由于第三方认证系统没有及时更新您的身份信息导致的。您可以联系客服寻求帮助（4008-588-066）。</p>
                    </div>
                </div>
                <div data-source="content_68" class="list-item">
                    <div class="list-item-title"><span class="anchor">一个手机号可以注册几个账户？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>手机号具有唯一性，故一个手机号只能注册绑定一个账户。</p>
                    </div>
                </div>
            </div>
        </div>
        <div data-source="topic_4" class="list-items help-box">
            <h1>充值/提现</h1>
            <div class="list-container">
                <div data-source="content_64" class="list-item">
                    <div class="list-item-title"><span class="anchor">平台提现手续费是怎么收取的？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">提现手续费=基本手续费+额外手续费</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: inherit; font-style: inherit; font-variant: inherit; font-weight: inherit; font-stretch: inherit; line-height: 1.6; vertical-align: baseline;">1、&nbsp;&nbsp;</span><span style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: inherit; font-style: inherit; font-variant: inherit; font-weight: 800; font-stretch: inherit; line-height: 1.6; vertical-align: baseline;">基本手续费：</span><span style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: inherit; font-style: inherit; font-variant: inherit; font-weight: inherit; font-stretch: inherit; line-height: 1.6; vertical-align: baseline;">每个月前2次提现免收基本手续费，超过2次后的提现基本手续费如下：</span></p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">提现金额小于等于1万：2元/笔</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">提现金额大于1万，小于等于5万：3元/笔</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">提现金额大于5万：5元/笔</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">2、<span style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: inherit; font-style: inherit; font-variant: inherit; font-weight: 800; font-stretch: inherit; line-height: inherit; vertical-align: baseline;">额外手续费：</span>充值未投资的资金如需要提现，将额外收取提现金额的0.3%，此部分手续费不包含免费范围内。</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: inherit; font-style: inherit; font-variant: inherit; font-weight: 800; font-stretch: inherit; line-height: inherit; vertical-align: baseline;">提现费用举例：</span></p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp; &nbsp;用户账户中有到期回款资金3万元，用户充值5万后（未投资），一次性提现8万元（用户该月已经用完2次免收提现基本手续费机会）：</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp; &nbsp;提现费用= 5元（基本手续费）+ 5万*0.3%（额外手续费）=155元</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: inherit; font-style: inherit; font-variant: inherit; font-weight: 800; font-stretch: inherit; line-height: inherit; vertical-align: baseline;">提现限额：</span></p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp; &nbsp;最低限额：当账户余额大于等于50元时，每笔提现最低限额为50元；当账户余额小于50元时，需一次性全部提现。</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp; &nbsp;最高限额：单笔限额50万（民生银行单笔限额5万），单日不限次数。</p>

                        <p align="left" style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: inherit; font-style: inherit; font-variant: inherit; font-weight: 800; font-stretch: inherit; line-height: inherit; vertical-align: baseline;">提现手续费规则将于2015年12月1日起正式实施。</span></p>
                    </div>
                </div>
                <div data-source="content_103" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何绑定银行卡？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>用户在注册成功后，在【账户中心】—【银行卡管理】页面中添加银行卡。并且，同一用户只能绑定一张银行卡。</p>
                    </div>
                </div>
                <div data-source="content_104" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是同卡进出？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>用户通过同名银行卡充值进来的资金，需要在提现的时候通过该卡返还给用户，且用户只能绑定唯一一张同名银行卡。用户如需解绑，需通过邮件提交资料至网利宝，资料审核通过后即可解绑银行卡.</p>
                    </div>
                </div>
                <div data-source="content_61" class="list-item">
                    <div class="list-item-title"><span class="anchor">平台充值提现须知</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span lang="EN-US" style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: 微软雅黑, sans-serif; font-style: normal; font-stretch: inherit; line-height: 30px; font-size: 10.5pt; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp;&nbsp;</span><span style="box-sizing: border-box; margin: 0px; padding: 0px; border: 0px; font-family: 微软雅黑, sans-serif; font-stretch: inherit; line-height: 30px; font-size: 10.5pt; vertical-align: baseline; color: rgb(102, 102, 102);">为了保证广大用户的资金安全，防止用户资金被他人恶意提现。目前，网利宝平台资金只能提现到实名认证人名下的银行卡中，即只能被提现到用户本人名下银行卡上。请广大用户悉知！</span></p>
                    </div>
                </div>
                <div data-source="content_13" class="list-item">
                    <div class="list-item-title"><span class="anchor">充值失败，无法从银行卡充值到网利宝怎么办？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>很有可能是您使用的网络延迟导致，建议您稍后再试。</p>
                    </div>
                </div>
                <div data-source="content_14" class="list-item">
                    <div class="list-item-title"><span class="anchor">银行已经扣款，但是网利宝账户余额却没有增加？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>用户可通过致电网利宝客服电话：4008-588-066，提交相关信息，网利宝工作人员将尽快为您解决。</p>
                    </div>
                </div>
                <div data-source="content_15" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何提现？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>在“我的账户”页面可以查看账户余额，发起提现申请以及查看提现记录。</p>
                    </div>
                </div>
                <div data-source="content_16" class="list-item">
                    <div class="list-item-title"><span class="anchor">申请提现后，资金多久可以到账？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>提现资金将在T+1~3个工作日到账到银行卡上。</p>
                    </div>
                </div>
                <div data-source="content_58" class="list-item">
                    <div class="list-item-title"><span class="anchor">充值是否有限额？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left" style="margin-left:10.5pt;">网利宝对于客户的充值次数和金额均无限制。客户在充值的单笔限额和次数均取决于充值银行。</p>
                    </div>
                </div>
                <div data-source="content_59" class="list-item">
                    <div class="list-item-title"><span class="anchor">提现限额是多少？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>最低限额：当账户余额大于等于50元时，每笔提现最低限额为50元；当账户余额小于50元时，需一次性提取完。</p>

                        <p>最高限额：单笔限额10万，单日不限次数。（自2015年12月1日起，单笔提现限额50万，民生银行单笔限额5万，单日不限次数）</p>
                    </div>
                </div>
                <div data-source="content_73" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何给账户充值？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>1. 登录网利宝，打开【我的账户】页面，点击【充值】；2. 跳转至充值页面，选择充值银行，输入充值金额，点击【充值】；3. 跳转至银行或者第三方支付页面，按照页面的提示输入银行账户和密码等信息即可完成充值。</p>
                    </div>
                </div>
                <div data-source="content_74" class="list-item">
                    <div class="list-item-title"><span class="anchor">充值是否收费？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>用户在网利宝平台充值的手续费，由网利宝平台承担。</p>
                    </div>
                </div>
                <div data-source="content_75" class="list-item">
                    <div class="list-item-title"><span class="anchor">提现未到账怎么办？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><div style="color: rgb(0, 0, 0); font-family: 微软雅黑, Tahoma; font-size: 14px; line-height: normal;"><span style="color: inherit !important; background-color: rgba(0, 0, 0, 0); font-size: 10.5pt; line-height: 1.5;">（1）客户银行卡号或银行卡状态异常(银行卡注销、挂失、过期等）导致失败</span></div>

                        <div style="color: rgb(0, 0, 0); font-family: 微软雅黑, Tahoma; font-size: 14px; line-height: normal;"><br>
          <span style="background-color: rgba(0, 0, 0, 0);">银行卡号异常：一般是用户银行卡号填写有误，需删除错误卡号，用正确的银行卡号进行提现；<br>
          <br>
          银行卡状态异常：客户需要按照换卡流程更换其他卡片</span><br>
                            <br>
                            <span style="color: inherit !important; background-color: rgba(0, 0, 0, 0); font-size: 10.5pt; line-height: 1.5;">（2）第三方平台处于处理中状态</span></div>

                        <div style="color: rgb(0, 0, 0); font-family: 微软雅黑, Tahoma; font-size: 14px; line-height: normal;"><br>
          <span style="background-color: rgba(0, 0, 0, 0);">需要等待对账完毕状态变更后，才能确认客户是成功还是失败，如果失败且客户银行卡没有问题，会再次给客户重新发送<br>
          <br>
          （3）异常提现（即提现银行卡非原充值卡）</span><br>
                            <br>
          <span style="background-color: rgba(0, 0, 0, 0);">a.客户提现至原充值卡内&nbsp;<br>
          <br>
          b.需提供相关资料详情参考异常取现处理办法</span></div>

                        <div style="color: rgb(0, 0, 0); font-family: 微软雅黑, Tahoma; font-size: 14px; line-height: normal;">&nbsp;</div>

                        <div style="color: rgb(0, 0, 0); font-family: 微软雅黑, Tahoma; font-size: 14px; line-height: normal;"><span style="background-color: rgba(0, 0, 0, 0);">（4）客户账号异常、重复被拦截或加入黑名单<br>
          <br>
          需联系客服</span><span style="font-size: 10.5pt; line-height: 1.5; background-color: window;">（4008-588-066）</span><span style="font-size: 10.5pt; line-height: 1.5; background-color: rgba(0, 0, 0, 0);">核实情况，解决后客户可重新提现</span></div>
                    </div>
                </div>
                <div data-source="content_76" class="list-item">
                    <div class="list-item-title"><span class="anchor">为什么提现申请会失败？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content">
                        <p>答：造成您提现失败的原因可能有以下几种：<br>
                            a、网利宝账号未通过实名认证<br>
                            b、银行开户行信息填写错误<br>
                            c、银行账号/户名错误，或是账号和户名不符<br>
                            d、提现银行卡为存折或信用卡<br>
                            e、银行账户冻结或正在办理挂失<br>
                            如果遇到以上情况，我们会在收到银行转账失败的通知后解除您的提现资金冻结，并及时通知您相关信息，请您不必担心资金安全。</p>
                    </div>
                </div>
                <div data-source="content_81" class="list-item">
                    <div class="list-item-title"><span class="anchor">异常提现所需资料</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p><span style="font-size:14px;">&nbsp; （1）身份证正反面照片</span></p>

                        <p><span style="font-size:14px;">&nbsp; （2）</span><span style="font-size: 14px; line-height: 1.6;">手持身份证照片</span></p>

                        <p><span style="font-size:14px;">&nbsp; （3）手持新卡照片</span></p>

                        <p><span style="font-size:14px;">&nbsp; （4）旧卡注销或挂失证明（如找不到注销或挂失证明请提供银行卡账户流水证明含在我司充值提现的交易流水）</span></p>

                        <p><span style="font-size:14px;">&nbsp; （5）新卡开户或账户证明。</span></p>
                    </div>
                </div>
                <div data-source="content_105" class="list-item">
                    <div class="list-item-title"><span class="anchor">提现限额是多少？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><div style="color: rgb(0, 0, 0); font-family: 微软雅黑; font-size: 14px; line-height: 21px;"><span style="font-size: 10.5pt; line-height: 1.5; background-color: window;">最低限额：当账户余额大于等于50元时，每笔提现最低限额为50元；当账户余额小于50元时，需一次性提取完。</span></div>

                        <p><span style="color: rgb(0, 0, 0); font-family: 微软雅黑; font-size: 14px; line-height: 21px;">最高限额：单笔提现限额50万，单日不限次数。</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div data-source="topic_3" class="list-items help-box">
            <h1>投资/赎回</h1>
            <div class="list-container">
                <div data-source="content_25" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何进行投资？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>投资步骤：注册--实名认证--充值到网利宝账户--选择投资项目购买。</p>
                    </div>
                </div>
                <div data-source="content_17" class="list-item">
                    <div class="list-item-title"><span class="anchor">如果出现项目未满标的情况，投资失败怎么办？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>如果在有效期间内投资项目的借款金额未投满，则流标，届时投资人的投资资金会自动返回到其网利宝个人账户。</p>
                    </div>
                </div>
                <div data-source="content_18" class="list-item">
                    <div class="list-item-title"><span class="anchor">投资标的是否真实存在？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>网利宝发布的所有投资标的都是真实有效的，均经过严格的筛选、实地检查和复核。</p>
                    </div>
                </div>
                <div data-source="content_19" class="list-item">
                    <div class="list-item-title"><span class="anchor">平台上投资项目的还款方式有哪些？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>网利宝平台有三种还款方式:1、按月等额本息还款；2、按月还息到期还本；3、到期一次性还本付息。</p>
                    </div>
                </div>
                <div data-source="content_20" class="list-item">
                    <div class="list-item-title"><span class="anchor">购买后可以提前赎回或转让吗？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>目前网利宝平台发布的月利宝和转让标投资项目可以提前赎回或转让。</p>
                    </div>
                </div>
                <div data-source="content_21" class="list-item">
                    <div class="list-item-title"><span class="anchor">账户中的投资中冻结金额和提现中冻结金额是什么意思？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>投资后，在款项还未划款给借款人之前，资金是处于投资冻结中的，待借款项目满标并审核通过后，平台放款给借款人开始计息时，投资中冻结金额就会为“0”。</p>

                        <p>从申请提现到资金到账到银行卡上的这段时间，资金将显示为提现冻结状态，当资金到达银行卡后提现资金显示为“0”。</p>
                    </div>
                </div>
                <div data-source="content_22" class="list-item">
                    <div class="list-item-title"><span class="anchor">购买后何时开始计息？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>散标：在投资项目的借款满标后，平台会对借款人进行放款，放款日即开始计息。<br>
                            月利宝：当日计息，投资用户在购买日即开始计息（购买日到实际计息日所得利息将于投资计划的第一个还款日统一发放）。</p>
                    </div>
                </div>
                <div data-source="content_55" class="list-item">
                    <div class="list-item-title"><span class="anchor">产品到期后资金需要多久到账？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>正常情况下，产品到期后资金会在1-3个工作日内到账到您的网利宝账户。</p>
                    </div>
                </div>
                <div data-source="content_56" class="list-item">
                    <div class="list-item-title"><span class="anchor">提前还款对投资人有什么好处？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>（1）投资人可以以更短的期限获得投资时约定的年化收益率</p>

                        <p>（2）更好地保证投资人的资金流动性</p>

                        <p>（3）较短时间内回款，降低投资人的资金风险</p>
                    </div>
                </div>
                <div data-source="content_57" class="list-item">
                    <div class="list-item-title"><span class="anchor">提前还款有罚息吗？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>是否有罚息取决该标的合同。</p>
                    </div>
                </div>
                <div data-source="content_69" class="list-item">
                    <div class="list-item-title"><span class="anchor">网利宝平台的收益率是多少？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>目前，网利宝平台预期年化6%-13%，具体收益率以网利宝平台为准。用户可通过参与网利宝平台活动，获得更高收益<span style="line-height: 1.6;">。</span></p>
                    </div>
                </div>
                <div data-source="content_70" class="list-item">
                    <div class="list-item-title"><span class="anchor">购买成功后是否可以撤销？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>所有项目在购买成功后均不可以撤销。</p>
                    </div>
                </div>
                <div data-source="content_71" class="list-item">
                    <div class="list-item-title"><span class="anchor">购买成功了，但在我的账户里看不到投资记录？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>当您的购买项目尚未满标或者处于满标审核中，您的购买资金会被冻结，待项目满标审核后，即可进入我的账户查看投资记录。</p>
                    </div>
                </div>
                <div data-source="content_72" class="list-item">
                    <div class="list-item-title"><span class="anchor">项目到期后，资金会回到哪里？收到还款后能马上再投资吗？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>项目到期后，本金和利息是回到您的网利宝账户的。账户内资金可以直接用来继续投资，确保投资人收益的最大化。</p>
                    </div>
                </div>
            </div>
        </div>
        <div data-source="topic_2" class="list-items help-box">
            <h1>账户密码管理</h1>
            <div class="list-container">
                <div data-source="content_63" class="list-item">
                    <div class="list-item-title"><span class="anchor">用户解绑银行卡需要提供什么？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p align="left"><span style="color: rgb(102, 102, 102); font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-size: 14px; line-height: 30px;">&nbsp; 根据第三方通道的银行卡变更及解绑要求，&nbsp;为方便用户变更或解除已绑定银行卡，特拟定此公告，具体解绑流程如下：</span></p>

                        <p><span style="font-size:14px;"><span lang="EN-US" style="font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp; &nbsp; &nbsp; 1</span></span><span style="color: rgb(102, 102, 102); font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-size: 14px; line-height: 30px;">、</span><span style="font-size:14px;"><span style="font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">用户换卡（绑定的银行卡属于正常状态，需要换本人的其它卡交易）：需核实网利宝账户资金已全部提走，可申请更换同人的银行卡。</span></span></p>

                        <p><span style="font-size:14px;"><span style="font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;"><span lang="EN-US">&nbsp; &nbsp; &nbsp;</span>提供资料至邮箱：<span lang="EN-US">jiesuan@wanglibank.com</span>。所需资料：网利宝账户总资产已全部提走的证明（例：用户对应帐户总资产为<span lang="EN-US">"0"</span>的截图）及 提供手持身份证、原银行卡、现银行卡照片（要求能看见脸）。</span></span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp; &nbsp; <span style="color: rgb(102, 102, 102); font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; line-height: 30px;">&nbsp;</span><span style="color: rgb(102, 102, 102); font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; line-height: 30px;">2、</span><span style="font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">用户换卡（绑定的银行卡：注销、挂失、补办、更换等）：需要用户提供开户银行的相关证明材料，通过审核后，可申请更换同人的银行卡。</span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp; &nbsp; &nbsp; <span style="font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">提供资料至邮箱：<span lang="EN-US">jiesuan@wanglibank.com</span>。所需资料：①银行卡变更申请书（补卡<span lang="EN-US">/</span>换卡申请书），②身份证正反面照片、手持身份证正面半身照，③新卡拍照、手持新卡拍照（要求能看见脸），④销卡凭证。注：同一银行换卡提供：①、②、③，不同银行换卡提供：②、③、④。</span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="font-size:14px;">&nbsp; &nbsp;&nbsp;&nbsp;3、用户注销：需核实该用户确实是要注销该银行卡，网利宝账户资金已全部提走。</span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="font-size:14px;">&nbsp; &nbsp; &nbsp; <span style="font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">提供资料至邮箱：<span lang="EN-US">jiesuan@wanglibank.com</span>。所需资料：网利宝账户总资产已全部提走的证明（例：用户对应帐户总资产为<span lang="EN-US">"0"</span>的截图）</span></span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp; &nbsp; &nbsp;<span style="font-size: 9.5pt; font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">用户解绑邮件，内容格式（必须按照此格式发）：</span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="font-size: 9.5pt; font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;解绑原因：用户换卡<span lang="EN-US">/</span>用户注销<span lang="EN-US">/</span>用户卡丢失</span>&nbsp;</p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="font-size: 9.5pt; font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;持卡人信息举例：</span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="font-size: 9.5pt; font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;注册手机号：<span lang="EN-US">138********</span></span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="font-size: 9.5pt; font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;身份证号：<span lang="EN-US">110***************</span></span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="font-size: 9.5pt; font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;银行卡号：<span lang="EN-US">6222***************</span></span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);"><span style="font-size: 9.5pt; font-family: 微软雅黑, sans-serif; color: rgb(102, 102, 102); background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;（附件添加所需提供的资料）</span></p>

                        <p style="box-sizing: border-box; margin: 0px 0px 15px; padding: 0px; border: 0px; font-family: 'Helvetica Neue', 'Hiragino Sans GB', 'Microsoft YaHei', 'WenQuanYi Micro Hei', sans-serif; font-stretch: inherit; line-height: 30px; font-size: 14px; vertical-align: baseline; color: rgb(102, 102, 102);">&nbsp;</p>
                    </div>
                </div>
                <div data-source="content_31" class="list-item">
                    <div class="list-item-title"><span class="anchor">忘记登录密码怎么办？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>在登录页面选择“忘记密码”可进行密码重置。</p>
                    </div>
                </div>
                <div data-source="content_32" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何修改登录密码？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>（1）点击屏幕右上角“我的账户”进入 （2）在左边菜单导航处，点击“修改密码” （3）填写原始密码，重新设定密码即可修改成功。</p>
                    </div>
                </div>
                <div data-source="content_60" class="list-item">
                    <div class="list-item-title"><span class="anchor">什么是交易密码？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>交易密码是供用户在充值、投资、提现申请操作时使用，作为用户承认本人操作的密码。为了您的交易安全，请尽快通过手机端设置交易密码，目前，电脑端暂无交易密码设置。</p>
                    </div>
                </div>
                <div data-source="content_77" class="list-item">
                    <div class="list-item-title"><span class="anchor">如何修改登录/交易密码？</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content"><p>电脑端：（1）点击 进入“我的账户”（2）在左边菜单导航处，点击“修改密码” （3）填写原始密码，重新设定密码即可修改成功。电脑端暂无交易密码设置。</p>

                        <p>手机端：（1）点击进入 “我的账户”（2）进入右上角“设置” （3）选择“修改登录密码”或“管理交易密码”。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<div id="sfooter"></div>
<script type="text/javascript" src="https://php1.wanglibao.com/js/sea.js"></script>
<script type="text/javascript" src="https://php1.wanglibao.com/js/sea-config.js"></script>
<script src='{!! env("STYLE_BASE_URL") !!}/js/jquery.min.js'></script>
<script src='{!! env("STYLE_BASE_URL") !!}/js/news_list.js'></script>
<script type="text/javascript">
    seajs.use(['jquery'],function($){
        seajs.use(['public', 'template', 'header'],function(){
        });
    });
</script>
</body>
</html>
