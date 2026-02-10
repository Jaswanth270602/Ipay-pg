<script>
(function() {
    'use strict';
    var app = angular.module('badlicashApp', []);
    app.controller('PaymentLinksController', ['$http', '$window', function($http, $window){
        var vm = this;
        vm.paymentLinks=[]; vm.pagination={current_page:1,per_page:10,total:0,last_page:1}; vm.perPage=10; vm.filters={status:'all',search:''}; vm.loading=false; vm.creating=false;
        vm.newLink={ title:'', description:'', amount:'', currency:'INR', expires_in_hours:24 };
        vm.toastMessage=''; vm.toastType='success';
        vm.loadPaymentLinks=loadPaymentLinks; vm.loadPage=loadPage; vm.getPages=getPages; vm.createPaymentLink=createPaymentLink; vm.copyLink=copyLink;
        loadPaymentLinks();
        function loadPaymentLinks(){ vm.loading=true; var params={page:vm.pagination.current_page, per_page:vm.perPage, status:vm.filters.status, search:vm.filters.search};
            $http.get('/merchant/payment-links/data',{params:params}).then(function(r){ vm.paymentLinks=r.data.data; vm.pagination=r.data.pagination; vm.loading=false; }, function(){ vm.loading=false; showToast('Failed to load payment links','error'); }); }
        function loadPage(p){ if(p<1||p>vm.pagination.last_page) return; vm.pagination.current_page=p; loadPaymentLinks(); }
        function getPages(){ var a=[],s=Math.max(1,vm.pagination.current_page-2),e=Math.min(vm.pagination.last_page,vm.pagination.current_page+2); for(var i=s;i<=e;i++) a.push(i); return a; }
        function createPaymentLink(){ vm.creating=true; var csrf=document.querySelector('meta[name="csrf-token"]').content; $http.post('/merchant/payment-links', vm.newLink, {headers:{'X-CSRF-TOKEN':csrf}}).then(function(){ vm.creating=false; showToast('Payment link created','success'); var m=bootstrap.Modal.getInstance(document.getElementById('createLinkModal')); if(m) m.hide(); vm.newLink={ title:'', description:'', amount:'', currency:'INR', expires_in_hours:24 }; loadPaymentLinks(); }, function(){ vm.creating=false; showToast('Failed to create link','error'); }); }
        function copyLink(link){ var url=$window.location.origin + '/pay/' + link.link_token; var t=document.createElement('textarea'); t.value=url; t.style.position='fixed'; t.style.opacity=0; document.body.appendChild(t); t.select(); try{ document.execCommand('copy'); showToast('Link copied','success'); }catch(e){ showToast('Copy failed','error'); } document.body.removeChild(t); }
        function showToast(msg,type){ vm.toastMessage=msg; vm.toastType=type; var el=document.getElementById('toast'); var toast=new bootstrap.Toast(el); toast.show(); }
    }]);
})();
</script>

