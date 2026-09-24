(()=>{
  if(!window.wp?.media)return;
  document.querySelectorAll('.bfnd-design-media').forEach(field=>{
    const input=field.querySelector('input[type=hidden]');
    const reset=field.querySelector('.bfnd-image-reset');
    const preview=field.querySelector('img');
    const fallback=preview?.dataset.default||'';
    let frame;
    field.querySelector('.bfnd-design-pick')?.addEventListener('click',()=>{
      if(!frame){
        frame=wp.media({title:'選取版面圖片',button:{text:'使用這張圖片'},multiple:false,library:{type:'image'}});
        frame.on('select',()=>{
          const image=frame.state().get('selection').first()?.toJSON();
          if(!image)return;
          input.value=String(image.id);
          if(reset)reset.value='0';
          preview.src=image.sizes?.medium?.url||image.url;
        });
      }
      frame.open();
    });
    field.querySelector('.bfnd-design-reset')?.addEventListener('click',()=>{input.value='';if(reset)reset.value='1';preview.src=fallback});
  });
  document.querySelectorAll('.bfnd-design-text-reset').forEach(button=>button.addEventListener('click',()=>{
    const input=button.parentElement.querySelector('textarea');
    if(input)input.value=input.dataset.default||'';
  }));
})();
