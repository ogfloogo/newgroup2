define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user_money_log/index' + location.search,
                    add_url: 'user/user_money_log/add',
                    edit_url: 'user/user_money_log/edit',
                    del_url: 'user/user_money_log/del',
                    multi_url: 'user/user_money_log/multi',
                    import_url: 'user/user_money_log/import',
                    table: 'user_money_log',
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
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN', formatter:function (value,row) {
                            return (row.mold=='dec'?'-':'+') + value ;
                         }},
                        {field: 'before', title: __('Before'), operate:'BETWEEN'},
                        {field: 'after', title: __('After'), operate:'BETWEEN'},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3'),"4":__('Type 4'),"5":__('Type 5'),"6":__('Type 6'),"7":__('Type 7'),"8":__('Type 8'),"9":__('Type 9'),"10":__('Type 10'),"11":__('Type 11'),"12":__('Type 12'),"13":__('Type 13'),"25":__('Type 25'),"28":__('Type 28'),"29":__('Type 29'),"35":__('Type 35')}, formatter: Table.api.formatter.normal},
                        {field: 'mold', title: __('Mold'), operate: 'LIKE'},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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
