(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-editaddress-editaddress"],{1399:function(t,e,i){"use strict";var a=i("3c05"),n=i.n(a);n.a},"24af":function(t,e,i){"use strict";i.r(e);var a=i("b404"),n=i("f602");for(var s in n)"default"!==s&&function(t){i.d(e,t,(function(){return n[t]}))}(s);i("1399");var c,d=i("f0c5"),r=Object(d["a"])(n["default"],a["b"],a["c"],!1,null,"77026f0c",null,!1,a["a"],c);e["default"]=r.exports},"3c05":function(t,e,i){var a=i("5069");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=i("4f06").default;n("5504f3ec",a,!0,{sourceMap:!1,shadowMode:!1})},5069:function(t,e,i){var a=i("24fb");e=a(!1),e.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */\r\n/* 主题配置 */uni-page-body[data-v-77026f0c]{background-color:#f6f6f6}.box1[data-v-77026f0c]{height:%?882?%;background-color:#fff;font-size:%?32?%;font-family:Rubik;font-weight:400;line-height:%?108?%;padding:0 %?40?%}.box1 .add[data-v-77026f0c]{height:%?108?%;display:flex;justify-content:space-between;border-bottom:%?2?% solid #ececec}.box1 .add .address[data-v-77026f0c]{color:#2b3039}.box1 .add .addressR[data-v-77026f0c]{color:#0f0f0e;display:flex;align-items:center;text-align:right}[data-v-77026f0c] .u-input{align-items:center}[data-v-77026f0c] .uni-input-input{text-align:right;color:#0f0f0e}.box2[data-v-77026f0c]{overflow:hidden;height:%?427?%;background-color:#fff;margin-top:%?20?%;padding:0 %?40?%}.box2 .as[data-v-77026f0c]{display:flex;justify-content:space-between}.box2 .as .set[data-v-77026f0c]{font-size:%?32?%;line-height:%?32?%;font-family:Rubik;font-weight:400;color:#2b3039;margin-top:%?45?%}.box2 .as .set .default[data-v-77026f0c]{display:flex;font-size:%?26?%;font-family:Rubik;font-weight:400;color:#bdbdbd;line-height:%?26?%;margin-top:%?21?%}.u-switch[data-v-77026f0c]{margin-top:%?47?%}[data-v-77026f0c] .u-switch__node{background-color:#fed104!important}.confirm[data-v-77026f0c]{width:%?670?%;height:%?90?%;background:#fed104;border-radius:%?10?%;font-size:%?30?%;font-family:Rubik;font-weight:400;color:#0f0f0e;line-height:%?90?%;text-align:center;position:fixed;bottom:%?30?%}body.?%PAGE?%[data-v-77026f0c]{background-color:#f6f6f6}',""]),t.exports=e},b404:function(t,e,i){"use strict";i.d(e,"b",(function(){return n})),i.d(e,"c",(function(){return s})),i.d(e,"a",(function(){return a}));var a={navbar:i("b50e").default,uInput:i("2977").default,uSwitch:i("d7d3").default},n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("v-uni-view",[a("navbar",{attrs:{title:"Edit address",background:"#FED104",titleColor:"#0F0F0E",backColor:"#0F0F0E"}}),a("v-uni-view",{staticClass:"box1"},[a("v-uni-view",{staticClass:"add"},[a("v-uni-view",{staticClass:"address"},[t._v(t._s(t.$t("editadd.name")))]),a("v-uni-view",{staticClass:"addressR"},[a("u-input",{attrs:{clearable:!1,"placeholder-style":"text-align:end",placeholder:""},model:{value:t.name,callback:function(e){t.name=e},expression:"name"}}),a("v-uni-image",{staticStyle:{width:"24rpx",height:"24rpx","margin-left":"30rpx"},attrs:{src:i("b6bf")},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clear1("name")}}})],1)],1),a("v-uni-view",{staticClass:"add"},[a("v-uni-view",{staticClass:"address"},[t._v(t._s(t.$t("editadd.ph")))]),a("v-uni-view",{staticClass:"addressR"},[a("u-input",{attrs:{clearable:!1,"placeholder-style":"text-align:end",placeholder:""},model:{value:t.mobile,callback:function(e){t.mobile=e},expression:"mobile"}}),a("v-uni-image",{staticStyle:{width:"24rpx",height:"24rpx","margin-left":"30rpx"},attrs:{src:i("b6bf")},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clear1("mobile")}}})],1)],1),a("v-uni-view",{staticClass:"add"},[a("v-uni-view",{staticClass:"address"},[t._v(t._s(t.$t("editadd.province")))]),a("v-uni-view",{staticClass:"addressR"},[a("u-input",{attrs:{clearable:!1,"placeholder-style":"text-align:end",placeholder:""},model:{value:t.province,callback:function(e){t.province=e},expression:"province"}}),a("v-uni-image",{staticStyle:{width:"24rpx",height:"24rpx","margin-left":"30rpx"},attrs:{src:i("b6bf")},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clear1("province")}}})],1)],1),a("v-uni-view",{staticClass:"add"},[a("v-uni-view",{staticClass:"address"},[t._v(t._s(t.$t("editadd.city")))]),a("v-uni-view",{staticClass:"addressR"},[a("u-input",{attrs:{clearable:!1,"placeholder-style":"text-align:end",placeholder:""},model:{value:t.city,callback:function(e){t.city=e},expression:"city"}}),a("v-uni-image",{staticStyle:{width:"24rpx",height:"24rpx","margin-left":"30rpx"},attrs:{src:i("b6bf")},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clear1("city")}}})],1)],1),a("v-uni-view",{staticClass:"add"},[a("v-uni-view",{staticClass:"address"},[t._v(t._s(t.$t("editadd.district")))]),a("v-uni-view",{staticClass:"addressR"},[a("u-input",{attrs:{clearable:!1,"placeholder-style":"text-align:end",placeholder:""},model:{value:t.county,callback:function(e){t.county=e},expression:"county"}}),a("v-uni-image",{staticStyle:{width:"24rpx",height:"24rpx","margin-left":"30rpx"},attrs:{src:i("b6bf")},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clear1("district")}}})],1)],1),a("v-uni-view",{staticClass:"add"},[a("v-uni-view",{staticClass:"address"},[t._v(t._s(t.$t("editadd.commune")))]),a("v-uni-view",{staticClass:"addressR"},[a("u-input",{attrs:{clearable:!1,"placeholder-style":"text-align:end",placeholder:""},model:{value:t.village,callback:function(e){t.village=e},expression:"village"}}),a("v-uni-image",{staticStyle:{width:"24rpx",height:"24rpx","margin-left":"30rpx"},attrs:{src:i("b6bf")},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clear1("commune")}}})],1)],1),a("v-uni-view",{staticClass:"add"},[a("v-uni-view",{staticClass:"address"},[t._v(t._s(t.$t("editadd.specific")))]),a("v-uni-view",{staticClass:"addressR"},[a("u-input",{attrs:{clearable:!1,"placeholder-style":"text-align:end",placeholder:""},model:{value:t.address,callback:function(e){t.address=e},expression:"address"}}),a("v-uni-image",{staticStyle:{width:"24rpx",height:"24rpx","margin-left":"30rpx"},attrs:{src:i("b6bf")},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clear1("specific")}}})],1)],1),a("v-uni-view",{staticClass:"add"},[a("v-uni-view",{staticClass:"address"},[t._v(t._s(t.$t("editadd.code")))]),a("v-uni-view",{staticClass:"addressR"},[a("u-input",{attrs:{clearable:!1,"placeholder-style":"text-align:end",placeholder:""},model:{value:t.postcode,callback:function(e){t.postcode=e},expression:"postcode"}}),a("v-uni-image",{staticStyle:{width:"24rpx",height:"24rpx","margin-left":"30rpx"},attrs:{src:i("b6bf")},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.clear1("postcode")}}})],1)],1)],1),a("v-uni-view",{staticClass:"box2"},[a("v-uni-view",{staticClass:"as"},[a("v-uni-view",{staticClass:"set"},[t._v(t._s(t.$t("editadd.set"))),a("v-uni-view",{staticClass:"default"},[a("v-uni-image",{staticStyle:{width:"26rpx",height:"22rpx"},attrs:{src:i("eaba")}}),a("v-uni-view",{},[t._v(t._s(t.$t("editadd.default")))])],1)],1),a("u-switch",{attrs:{"active-color":"#fed10475","active-value":1,"inactive-value":0},model:{value:t.is_default,callback:function(e){t.is_default=e},expression:"is_default"}})],1),a("v-uni-view",{staticClass:"confirm",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.editadd.apply(void 0,arguments)}}},[t._v(t._s(t.$t("editadd.confirm")))])],1)],1)},s=[]},b6bf:function(t,e){t.exports="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAABJBJREFUaEPlWk1oHGUYfp6d2fxgkyb1VCw0BxVREbGJIIiiULQpSBNhD809N0+C51wrPXlLzkkDS7IphaamokEFDyb+UEgOIWIrVE8mqalsMpn5XnnDTNhudnb2m50NXRzYw+7OfN/7Ps/783zfNwQAEeHU1JTb39/fEwRBr+M4ruM4e+Vy+Z/Nzc3HExMTRu876atYLDqe5z3T3d3dGwRBF4AAwKPt7e3d8fFxn6RQjZqcnMz39PSccV33HMmzxphOAGURedjZ2flgZGRk56SN1/kWFhb69vf3z5N8DkA3AI/knwAeAtgqFAoeI+Mdx3kLwNskXwVwSm8WkZVcLnfX87yfx8bGtk/SiWKxeEZE3gBwkeQQgA4A/5JcE5Hvfd//YXd3d4t6ozFmgORVksMAXgJwyAyANZJ3jTFLJFcKhcLWSThRKpWe9X1/SEQ+IHkRwCvhvAJgA8BtY8xMEAS/c3Z2dsBxHL3hEwDvA8hXGOkBeABgmeS853krrWYiRH6I5CiA9wCcD9GPzPIBfC0iX7iuu8a5ubkXROR1EfkMwGAMwur1HRG500omIuRJXhKRDwG8GGPPKsnPReQXzs/Pn/N9/zWSn4YM1HrmIGTim1YxUYW8RoIiXxkNlXYti8h113XvcXp6urerq2tARK6KyEcAnq/zYEuYsEBegfxNRG6JyI1cLnefy8vL7s7Ozinf9y8AuCwil+tQlzkTlshviMgiycV8Pr/S19f3OKo2sB0oi5ywRP6PMHlL+Xx+dXR09G+NqSMH9IvlgFqdUueELWAioqV80XXdlcj4Yw7oD7YDp2HCEqhD5LUxA/ixuhc9wUCU5pYTWDFhC1Ac8pGtNR1ohonK+Kyux5bA1EU+0YG0OSEipVrNLmvkG3IgDRMkvzTGaKk70k6R8blcbjihw2qZbgj5hh1IycRXJG+qdtLnOzo6VJhdUWWZ0GG1ztesNnEiMjYHqh+wDIF1kktaNcJx3lRlCeDlGEOOGmRctWnaAUsmyqF2+iuc+GyIvC5Kal0bGnoqFqvrfJzhViFUOYgFE6rdK69abB8hH5f8mTtgyUTS/KmRT81A9GBFZbkiIu8A0HXr6SSLw/8f6bqW5HfGmJvNrDEaTuJahqkTJAfDBI2SNGlMDa11AFptlkRktZmlatJkiYAWi8XTIjJMchzAu9UCscYA6sC3IjKl4qxQKCgbqa//twNtHUIzMzP92mEBjLRdElf0gksA9BO3e5AU2ydfRiPkReTjcBcjbvfg6WtkFsg/fVLCAnkNm9RizlZSNFRGLZCPtE1qOW0r6hIdsET+MCmbXNAcrrEbZaKuAymQj53YQsVqCDZcnWIdSIN8kp63XNQ3xERNB7JEvroRZM3EMQdagXy1E1kycWxr8eDgYFD35xM6bNMrqayYSL25a1vuammKLJhIvb3eaJlLEkMpmLitZ2Su6/50uL1ue8CRBfJN5sQmyVskb+zt7d23PmLKCvkmq5Nu618XkXvWh3z1Nm+TwiXpf4ucWAVwDcCvbX3MaoxZb/+D7rZ/1UDjsq1f9lAH2vl1m/8A21LuUatEArIAAAAASUVORK5CYII="},f602:function(t,e,i){"use strict";i.r(e);var a=i("fc66"),n=i.n(a);for(var s in a)"default"!==s&&function(t){i.d(e,t,(function(){return a[t]}))}(s);e["default"]=n.a},fc66:function(t,e,i){"use strict";var a=i("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0,i("96cf");var n=a(i("1da1")),s=i("732bb"),c={data:function(){return{name:"",mobile:"",city:"",county:"",village:"",address:"",is_default:0,checked:!1,postcode:"",province:"",id:1}},methods:{clear1:function(t){this.$data[t]&&(this.$data[t]="")},editadd:function(){var t=this;return(0,n.default)(regeneratorRuntime.mark((function e(){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:if(t.name&&t.mobile&&t.postcode&&t.province&&t.city&&t.county&&t.village&&t.address){e.next=3;break}return uni.showToast({icon:"none",title:t.$t("editadd.toast"),position:"center"}),e.abrupt("return");case 3:return uni.showLoading({}),e.next=6,(0,s.editaddress)({name:t.name,mobile:t.mobile,postcode:t.postcode,province:t.province,city:t.city,county:t.county,village:t.village,address:t.address,is_default:t.is_default?1:0,id:t.id});case 6:uni.hideLoading(),uni.navigateBack({});case 8:case"end":return e.stop()}}),e)})))()}},onLoad:function(t){var e=JSON.parse(decodeURIComponent(t.addinfo));console.log(e),this.name=e.name,this.mobile=e.mobile,this.postcode=e.postcode,this.province=e.province,this.city=e.city,this.county=e.county,this.village=e.village,this.address=e.address,this.is_default=e.is_default,this.id=e.id}};e.default=c}}]);