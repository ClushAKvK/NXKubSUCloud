"use strict";(()=>{(self.webpackChunktalk=self.webpackChunktalk||[]).push([["defaultVendors-src_components_ConversationIcon_vue"],{26621:(a,i,_)=>{_.d(i,{A:()=>d});var s=_(71354),o=_.n(s),l=_(76314),e=_.n(l),n=e()(o());n.push([a.id,".conversation-icon[data-v-8bd20e9e]{width:var(--icon-size);height:var(--icon-size);position:relative}.conversation-icon .avatar.icon[data-v-8bd20e9e]{display:block;width:var(--icon-size);height:var(--icon-size);line-height:var(--icon-size);background-size:calc(var(--icon-size)/2);background-color:var(--color-text-maxcontrast-default)}.conversation-icon .avatar.icon.icon-changelog[data-v-8bd20e9e]{background-size:cover !important}.conversation-icon--dark .avatar.icon[data-v-8bd20e9e]{background-color:#3b3b3b}.conversation-icon__type[data-v-8bd20e9e]{position:absolute;right:-4px;bottom:-4px;height:18px;width:18px;border:2px solid var(--color-main-background);background-color:var(--color-main-background);border-radius:50%}.conversation-icon .overlap-icon[data-v-8bd20e9e]{position:absolute;top:0;left:calc(var(--icon-size) - 12px);line-height:100%;display:inline-block;vertical-align:middle}.offline[data-v-8bd20e9e]{opacity:.4}","",{version:3,sources:["webpack://./src/components/ConversationIcon.vue"],names:[],mappings:"AACA,oCACC,sBAAA,CACA,uBAAA,CACA,iBAAA,CAEA,iDACC,aAAA,CACA,sBAAA,CACA,uBAAA,CACA,4BAAA,CACA,wCAAA,CACA,sDAAA,CAEA,gEACC,gCAAA,CAIF,uDACC,wBAAA,CAGD,0CACC,iBAAA,CACA,UAAA,CACA,WAAA,CACA,WAAA,CACA,UAAA,CACA,6CAAA,CACA,6CAAA,CACA,iBAAA,CAGD,kDACC,iBAAA,CACA,KAAA,CACA,kCAAA,CACA,gBAAA,CACA,oBAAA,CACA,qBAAA,CAIF,0BACC,UAAA",sourcesContent:[`
.conversation-icon {
	width: var(--icon-size);
	height: var(--icon-size);
	position: relative;

	.avatar.icon {
		display: block;
		width: var(--icon-size);
		height: var(--icon-size);
		line-height: var(--icon-size);
		background-size: calc(var(--icon-size) / 2);
		background-color: var(--color-text-maxcontrast-default);

		&.icon-changelog {
			background-size: cover !important;
		}
	}

	&--dark .avatar.icon {
		background-color: #3B3B3B;
	}

	&__type {
		position: absolute;
		right: -4px;
		bottom: -4px;
		height: 18px;
		width: 18px;
		border: 2px solid var(--color-main-background);
		background-color: var(--color-main-background);
		border-radius: 50%;
	}

	.overlap-icon {
		position: absolute;
		top: 0;
		left: calc(var(--icon-size) - 12px);
		line-height: 100%;
		display: inline-block;
		vertical-align: middle;
	}
}

.offline {
	opacity: .4;
}

`],sourceRoot:""}]);const d=n},69475:(a,i,_)=>{_.d(i,{A:()=>E});var s=_(49117),o=_(36330),l=_(45987),e=_(37964),n=_(87485),d=_(41944),r=_(72925),C=_(27393),c=_(33015);const A=(0,n.F)()?.spreed?.features?.includes("avatar"),E={name:"ConversationIcon",components:{NcAvatar:d.A,Star:o.A,VideoIcon:l.A},props:{hideFavorite:{type:Boolean,default:!0},hideCall:{type:Boolean,default:!0},disableMenu:{type:Boolean,default:!0},hideUserStatus:{type:Boolean,default:!1},showUserOnlineStatus:{type:Boolean,default:!1},item:{type:Object,default(){return{objectType:"",type:0,displayName:"",isFavorite:!1}}},offline:{type:Boolean,default:!1},size:{type:Number,default:r.of.SIZE.DEFAULT}},computed:{showCall(){return!this.hideCall&&this.item.hasCall},showFavorite(){return!this.hideFavorite&&this.item.isFavorite},preloadedUserStatus(){if(!this.hideUserStatus&&Object.prototype.hasOwnProperty.call(this.item,"statusMessage"))return{status:this.item.status||null,message:this.item.statusMessage||null,icon:this.item.statusIcon||null}},menuContainer(){return this.$store?.getters.getMainContainerSelector()},iconClass(){if(this.item.isDummyConversation)return this.item.type===r.si.TYPE.PUBLIC?"icon-public":"icon-contacts";if(!A)return this.item.objectType===r.si.OBJECT_TYPE.FILE?"icon-file":this.item.objectType===r.si.OBJECT_TYPE.VIDEO_VERIFICATION?"icon-password":this.item.objectType===r.si.OBJECT_TYPE.EMAIL?"icon-mail":this.item.objectType===r.si.OBJECT_TYPE.PHONE?"icon-phone":this.item.objectType===r.si.OBJECT_TYPE.CIRCLES?"icon-team":this.item.type===r.si.TYPE.CHANGELOG?"icon-changelog":this.item.type===r.si.TYPE.ONE_TO_ONE_FORMER?"icon-user":this.item.type===r.si.TYPE.GROUP?"icon-contacts":this.item.type===r.si.TYPE.PUBLIC?"icon-public":void 0;if(!this.item.token){if(this.item.objectType===r.si.OBJECT_TYPE.CIRCLES)return"icon-team";if(this.item.type===r.si.TYPE.GROUP)return"icon-contacts"}},themeClass(){return`conversation-icon--${c.g?"dark":"bright"}`},isOneToOne(){return this.item.type===r.si.TYPE.ONE_TO_ONE},conversationType(){return this.item.remoteServer?{icon:e.A,label:t("spreed","Federated conversation")}:this.item.type===r.si.TYPE.PUBLIC?{icon:s.A,label:t("spreed","Public conversation")}:null},avatarUrl(){if(!(!A||this.item.isDummyConversation))return(0,C.nv)(this.item.token,c.g,this.item.avatarVersion)}}}},56080:(a,i,_)=>{_.d(i,{X:()=>s,Y:()=>o});var s=function(){var e=this,n=e._self._c;return n("div",{staticClass:"conversation-icon",class:[e.themeClass,{offline:e.offline}],style:{"--icon-size":`${e.size}px`}},[e.isOneToOne?n("NcAvatar",{key:e.item.token,staticClass:"conversation-icon__avatar",attrs:{size:e.size,user:e.item.name,"disable-menu":e.disableMenu,"display-name":e.item.displayName,"preloaded-user-status":e.preloadedUserStatus,"show-user-status":!e.hideUserStatus,"show-user-status-compact":!e.showUserOnlineStatus,"menu-container":e.menuContainer}}):[e.iconClass?n("div",{staticClass:"avatar icon",class:e.iconClass}):n("img",{key:e.avatarUrl,staticClass:"avatar icon",attrs:{src:e.avatarUrl,width:e.size,height:e.size,alt:e.item.displayName}}),e._v(" "),!e.hideUserStatus&&e.conversationType?n("span",{staticClass:"conversation-icon__type",attrs:{role:"img","aria-hidden":"false","aria-label":e.conversationType.label}},[n(e.conversationType.icon,{tag:"component",attrs:{size:14}})],1):e._e()],e._v(" "),e.showCall?n("div",{staticClass:"overlap-icon"},[n("VideoIcon",{attrs:{size:20,"fill-color":"#E9322D"}}),e._v(" "),n("span",{staticClass:"hidden-visually"},[e._v(e._s(e.t("spreed","Call in progress")))])],1):e.showFavorite?n("div",{staticClass:"overlap-icon"},[n("Star",{attrs:{size:20,"fill-color":"#FFCC00"}}),e._v(" "),n("span",{staticClass:"hidden-visually"},[e._v(e._s(e.t("spreed","Favorite")))])],1):e._e()],2)},o=[]},33015:(a,i,_)=>{_.d(i,{g:()=>o});/*
 * @copyright Copyright (c) 2023 Grigorii Shartsev <me@shgk.me>
 *
 * @author Grigorii Shartsev <me@shgk.me>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */function s(){return window.getComputedStyle(document.body).getPropertyValue("--background-invert-if-dark")==="invert(100%)"}const o=s()},30940:(a,i,_)=>{var s=_(85072),o=_.n(s),l=_(97825),e=_.n(l),n=_(77659),d=_.n(n),r=_(55056),C=_.n(r),c=_(10540),A=_.n(c),E=_(41113),m=_.n(E),v=_(26621),u={};u.styleTagTransform=m(),u.setAttributes=C(),u.insert=d().bind(null,"head"),u.domAPI=e(),u.insertStyleElement=A();var p=o()(v.A,u),P=v.A&&v.A.locals?v.A.locals:void 0},49117:(a,i,_)=>{_.d(i,{A:()=>n});var s=_(48455),o=_(1579),l=_(14486),e=(0,l.A)(o.A,s.X,s.Y,!1,null,null,null);const n=e.exports},62500:(a,i,_)=>{_.d(i,{A:()=>s});const s={name:"LinkVariantIcon",emits:["click"],props:{title:{type:String},fillColor:{type:String,default:"currentColor"},size:{type:Number,default:24}}}},36330:(a,i,_)=>{_.d(i,{A:()=>n});var s=_(70195),o=_(13934),l=_(14486),e=(0,l.A)(o.A,s.X,s.Y,!1,null,null,null);const n=e.exports},68207:(a,i,_)=>{_.d(i,{A:()=>s});const s={name:"StarIcon",emits:["click"],props:{title:{type:String},fillColor:{type:String,default:"currentColor"},size:{type:Number,default:24}}}},45987:(a,i,_)=>{_.d(i,{A:()=>n});var s=_(76107),o=_(78377),l=_(14486),e=(0,l.A)(o.A,s.X,s.Y,!1,null,null,null);const n=e.exports},92438:(a,i,_)=>{_.d(i,{A:()=>s});const s={name:"VideoIcon",emits:["click"],props:{title:{type:String},fillColor:{type:String,default:"currentColor"},size:{type:Number,default:24}}}},37964:(a,i,_)=>{_.d(i,{A:()=>n});var s=_(39095),o=_(18866),l=_(14486),e=(0,l.A)(o.A,s.X,s.Y,!1,null,null,null);const n=e.exports},86205:(a,i,_)=>{_.d(i,{A:()=>s});const s={name:"WebIcon",emits:["click"],props:{title:{type:String},fillColor:{type:String,default:"currentColor"},size:{type:Number,default:24}}}},97626:(a,i,_)=>{_.d(i,{A:()=>d});var s=_(56080),o=_(20770),l=_(15483),e=_(14486),n=(0,e.A)(o.A,s.X,s.Y,!1,null,"8bd20e9e",null);const d=n.exports},20770:(a,i,_)=>{_.d(i,{A:()=>o});var s=_(69475);const o=s.A},15483:(a,i,_)=>{var s=_(30940)},1579:(a,i,_)=>{_.d(i,{A:()=>o});var s=_(62500);const o=s.A},13934:(a,i,_)=>{_.d(i,{A:()=>o});var s=_(68207);const o=s.A},78377:(a,i,_)=>{_.d(i,{A:()=>o});var s=_(92438);const o=s.A},18866:(a,i,_)=>{_.d(i,{A:()=>o});var s=_(86205);const o=s.A},48455:(a,i,_)=>{_.d(i,{X:()=>s,Y:()=>o});var s=function(){var e=this,n=e._self._c;return n("span",e._b({staticClass:"material-design-icon link-variant-icon",attrs:{"aria-hidden":e.title?null:"true","aria-label":e.title,role:"img"},on:{click:function(d){return e.$emit("click",d)}}},"span",e.$attrs,!1),[n("svg",{staticClass:"material-design-icon__svg",attrs:{fill:e.fillColor,width:e.size,height:e.size,viewBox:"0 0 24 24"}},[n("path",{attrs:{d:"M10.59,13.41C11,13.8 11,14.44 10.59,14.83C10.2,15.22 9.56,15.22 9.17,14.83C7.22,12.88 7.22,9.71 9.17,7.76V7.76L12.71,4.22C14.66,2.27 17.83,2.27 19.78,4.22C21.73,6.17 21.73,9.34 19.78,11.29L18.29,12.78C18.3,11.96 18.17,11.14 17.89,10.36L18.36,9.88C19.54,8.71 19.54,6.81 18.36,5.64C17.19,4.46 15.29,4.46 14.12,5.64L10.59,9.17C9.41,10.34 9.41,12.24 10.59,13.41M13.41,9.17C13.8,8.78 14.44,8.78 14.83,9.17C16.78,11.12 16.78,14.29 14.83,16.24V16.24L11.29,19.78C9.34,21.73 6.17,21.73 4.22,19.78C2.27,17.83 2.27,14.66 4.22,12.71L5.71,11.22C5.7,12.04 5.83,12.86 6.11,13.65L5.64,14.12C4.46,15.29 4.46,17.19 5.64,18.36C6.81,19.54 8.71,19.54 9.88,18.36L13.41,14.83C14.59,13.66 14.59,11.76 13.41,10.59C13,10.2 13,9.56 13.41,9.17Z"}},[e.title?n("title",[e._v(e._s(e.title))]):e._e()])])])},o=[]},70195:(a,i,_)=>{_.d(i,{X:()=>s,Y:()=>o});var s=function(){var e=this,n=e._self._c;return n("span",e._b({staticClass:"material-design-icon star-icon",attrs:{"aria-hidden":e.title?null:"true","aria-label":e.title,role:"img"},on:{click:function(d){return e.$emit("click",d)}}},"span",e.$attrs,!1),[n("svg",{staticClass:"material-design-icon__svg",attrs:{fill:e.fillColor,width:e.size,height:e.size,viewBox:"0 0 24 24"}},[n("path",{attrs:{d:"M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"}},[e.title?n("title",[e._v(e._s(e.title))]):e._e()])])])},o=[]},76107:(a,i,_)=>{_.d(i,{X:()=>s,Y:()=>o});var s=function(){var e=this,n=e._self._c;return n("span",e._b({staticClass:"material-design-icon video-icon",attrs:{"aria-hidden":e.title?null:"true","aria-label":e.title,role:"img"},on:{click:function(d){return e.$emit("click",d)}}},"span",e.$attrs,!1),[n("svg",{staticClass:"material-design-icon__svg",attrs:{fill:e.fillColor,width:e.size,height:e.size,viewBox:"0 0 24 24"}},[n("path",{attrs:{d:"M17,10.5V7A1,1 0 0,0 16,6H4A1,1 0 0,0 3,7V17A1,1 0 0,0 4,18H16A1,1 0 0,0 17,17V13.5L21,17.5V6.5L17,10.5Z"}},[e.title?n("title",[e._v(e._s(e.title))]):e._e()])])])},o=[]},39095:(a,i,_)=>{_.d(i,{X:()=>s,Y:()=>o});var s=function(){var e=this,n=e._self._c;return n("span",e._b({staticClass:"material-design-icon web-icon",attrs:{"aria-hidden":e.title?null:"true","aria-label":e.title,role:"img"},on:{click:function(d){return e.$emit("click",d)}}},"span",e.$attrs,!1),[n("svg",{staticClass:"material-design-icon__svg",attrs:{fill:e.fillColor,width:e.size,height:e.size,viewBox:"0 0 24 24"}},[n("path",{attrs:{d:"M16.36,14C16.44,13.34 16.5,12.68 16.5,12C16.5,11.32 16.44,10.66 16.36,10H19.74C19.9,10.64 20,11.31 20,12C20,12.69 19.9,13.36 19.74,14M14.59,19.56C15.19,18.45 15.65,17.25 15.97,16H18.92C17.96,17.65 16.43,18.93 14.59,19.56M14.34,14H9.66C9.56,13.34 9.5,12.68 9.5,12C9.5,11.32 9.56,10.65 9.66,10H14.34C14.43,10.65 14.5,11.32 14.5,12C14.5,12.68 14.43,13.34 14.34,14M12,19.96C11.17,18.76 10.5,17.43 10.09,16H13.91C13.5,17.43 12.83,18.76 12,19.96M8,8H5.08C6.03,6.34 7.57,5.06 9.4,4.44C8.8,5.55 8.35,6.75 8,8M5.08,16H8C8.35,17.25 8.8,18.45 9.4,19.56C7.57,18.93 6.03,17.65 5.08,16M4.26,14C4.1,13.36 4,12.69 4,12C4,11.31 4.1,10.64 4.26,10H7.64C7.56,10.66 7.5,11.32 7.5,12C7.5,12.68 7.56,13.34 7.64,14M12,4.03C12.83,5.23 13.5,6.57 13.91,8H10.09C10.5,6.57 11.17,5.23 12,4.03M18.92,8H15.97C15.65,6.75 15.19,5.55 14.59,4.44C16.43,5.07 17.96,6.34 18.92,8M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"}},[e.title?n("title",[e._v(e._s(e.title))]):e._e()])])])},o=[]},41944:(a,i,_)=>{_.d(i,{A:()=>s.N});var s=_(28948)}}]);})();

//# sourceMappingURL=talk-defaultVendors-src_components_ConversationIcon_vue.js.map?v=e3c1bca09a4fe1f68ba0