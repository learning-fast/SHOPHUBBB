/* script.js — Clean ShopHub Logic */

// ---------- TOAST NOTIFICATION ----------

function showToast(message){

const existing=document.querySelector('.toast');

if(existing) existing.remove();

const toast=document.createElement('div');

toast.className='toast show';

toast.innerText=message;

document.body.appendChild(toast);

setTimeout(()=>{

toast.classList.remove('show');

setTimeout(()=>toast.remove(),300);

},3000);

}


// ---------- QUANTITY BUTTONS ----------

function changeQty(change){

let qty=document.getElementById("quantity");

if(!qty) return;

let current=parseInt(qty.value) || 1;

current=current + change;

if(current<1){

current=1;

}

qty.value=current;

}


// ---------- SLIDESHOW ----------

let slideIndex=0;

function startSlideshow(){

const slides=document.querySelectorAll('.slide');

if(slides.length===0) return;

slides.forEach(slide=>slide.style.display='none');

slideIndex++;

if(slideIndex>slides.length){

slideIndex=1;

}

slides[slideIndex-1].style.display='block';

setTimeout(startSlideshow,3000);

}


// ---------- CATEGORY FILTER ----------

function filterProducts(category){

const products=document.querySelectorAll('.product-card');

const buttons=document.querySelectorAll('.btn-filter');

buttons.forEach(btn=>btn.classList.remove('active'));

products.forEach(p=>{

if(category==='all'||p.classList.contains(category)){

p.style.display='flex';

}else{

p.style.display='none';

}

});

}


// ---------- INITIALIZATION ----------

document.addEventListener('DOMContentLoaded',()=>{

startSlideshow();

initShopHubChatbot();

});


// ---------- SHOPPING ASSISTANT ----------

function initShopHubChatbot(){

const widget=document.querySelector('[data-chatbot-widget]');

if(!widget) return;

const toggle=widget.querySelector('[data-chatbot-toggle]');

const panel=widget.querySelector('[data-chatbot-panel]');

const closeBtn=widget.querySelector('[data-chatbot-close]');

const form=widget.querySelector('[data-chatbot-form]');

const input=widget.querySelector('[data-chatbot-input]');

const messages=widget.querySelector('[data-chatbot-messages]');

const quickPrompts=widget.querySelectorAll('[data-chatbot-prompt]');

const addMessage=(text,type='bot')=>{

const bubble=document.createElement('div');

bubble.className=`chatbot-message ${type}`;

bubble.textContent=text;

messages.appendChild(bubble);

messages.scrollTop=messages.scrollHeight;

return bubble;

};

const addProducts=(products=[])=>{

if(products.length===0) return;

const list=document.createElement('div');

list.className='chatbot-products';

products.forEach(product=>{

const item=document.createElement('a');

item.className='chatbot-product';

item.href=product.url;

item.innerHTML=`
<img src="/SHOPHUBBB/${product.image}" alt="">
<span>
<strong>${escapeHtml(product.name)}</strong>
<small>${escapeHtml(product.category)} - Rs. ${Number(product.price).toFixed(2)}</small>
</span>
`;

list.appendChild(item);

});

messages.appendChild(list);

messages.scrollTop=messages.scrollHeight;

};

toggle.addEventListener('click',()=>{

widget.classList.toggle('open');

if(widget.classList.contains('open')) input.focus();

});

closeBtn.addEventListener('click',()=>{

widget.classList.remove('open');

});

form.addEventListener('submit',async event=>{

event.preventDefault();

const text=input.value.trim();

if(!text) return;

addMessage(text,'user');

input.value='';

const loading=addMessage('Finding the best matches...','bot');

try{

const response=await fetch('/SHOPHUBBB/pages/chatbot.php',{

method:'POST',

headers:{'Content-Type':'application/json'},

body:JSON.stringify({message:text})

});

const data=await response.json();

loading.remove();

if(!response.ok){

addMessage(data.error || 'Something went wrong. Please try again.','bot');

return;

}

addMessage(data.reply || 'Here is what I found.','bot');

addProducts(data.products || []);

}catch(error){

loading.remove();

addMessage('I could not reach the assistant. Please check your local server and try again.','bot');

}

});

quickPrompts.forEach(button=>{

button.addEventListener('click',()=>{

input.value=button.dataset.chatbotPrompt || '';

form.requestSubmit();

});

});

}

function escapeHtml(value){

return String(value)

.replaceAll('&','&amp;')

.replaceAll('<','&lt;')

.replaceAll('>','&gt;')

.replaceAll('"','&quot;')

.replaceAll("'","&#039;");

}
