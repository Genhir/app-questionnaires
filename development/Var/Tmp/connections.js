SimpleForm.Connections=function(a,b){this.a=null;this.Name=a;this.H=!!b;this.c=[];this.j={}};
SimpleForm.Connections.prototype={Init:function(a){this.a=a;this.c=a.b[this.Name];var b=this;a=this.a.h;for(var c=0,d=this.c.length;c<d;c+=1)a(this.c[c],"change",function(){y(b);z(b)});y(this);z(this)}};function z(a){var b,c;b=a.a;for(var d=b.u,f=b.G,e=0,g=a.c.length;e<g;e+=1)b=a.c[e],c=b.parentNode,a.j[e]?(d(b,"error"),d(c,"error")):(f(b,"error"),f(c,"error"))}
function y(a){var b,c={},d,f=/[^0-9]/g;a.j={};for(var e=0,g=a.c.length;e<g;e+=1)b=a.c[e],b=b.value.replace(f,""),d=parseInt(b,10),0<b.length&&!(0<d&&d<g+1)?a.j[e]=!0:0<b.length&&"undefined"==typeof c[b]?c[b]=e:0<b.length&&(a.j[e]=!0,a.j[c[b]]=!0)};