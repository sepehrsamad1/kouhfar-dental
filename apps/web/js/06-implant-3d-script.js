
(() => {
  const renderDentalImplant = async (canvas) => {
    if (!canvas || canvas.dataset.ready) return;
    canvas.dataset.ready = '1';
    canvas.style.background='center / contain no-repeat url(assets/a-026.png)';
    try {
      const THREE = await import('assets/a-027.js');
      const renderer = new THREE.WebGLRenderer({canvas,alpha:true,antialias:true});
      renderer.setPixelRatio(Math.min(devicePixelRatio || 1, 2.5));
      renderer.setClearColor(0x000000,0);
      renderer.outputColorSpace = THREE.SRGBColorSpace;
      renderer.toneMapping = THREE.ACESFilmicToneMapping;
      renderer.toneMappingExposure = 1.12;
      const scene = new THREE.Scene();
      const camera = new THREE.PerspectiveCamera(30,1,.1,60);
      camera.position.set(0,.65,10.1);
      camera.lookAt(0,-.12,0);
      const root = new THREE.Group();
      root.rotation.set(0,-.18,-.025);
      scene.add(root);

      // Embedded photographic studio lighting: broad softboxes reflected by titanium.
      const ew=512,eh=256,envPixels=new Float32Array(ew*eh*4);
      const boxes=[[.20,.46,.065,.24,4.8],[.73,.43,.12,.29,3.1],[.46,.2,.23,.065,2.8]];
      for(let y=0;y<eh;y++) for(let x=0;x<ew;x++){
        const u=x/ew,v=y/eh;let value=.12+.2*(1-v);
        for(const [cx,cy,wx,wy,power] of boxes){
          const dx=Math.min(Math.abs(u-cx),1-Math.abs(u-cx))/wx,dy=Math.abs(v-cy)/wy;
          value+=power*Math.exp(-Math.pow(dx,6)-Math.pow(dy,6));
        }
        const i=(y*ew+x)*4;envPixels[i]=value;envPixels[i+1]=value;envPixels[i+2]=value*1.025;envPixels[i+3]=1;
      }
      const envTexture=new THREE.DataTexture(envPixels,ew,eh,THREE.RGBAFormat,THREE.FloatType);
      envTexture.mapping=THREE.EquirectangularReflectionMapping;envTexture.needsUpdate=true;
      const pmrem=new THREE.PMREMGenerator(renderer);
      const environment=pmrem.fromEquirectangular(envTexture);
      scene.environment=environment.texture;envTexture.dispose();pmrem.dispose();
      const ceramic=new THREE.MeshPhysicalMaterial({color:0xf3f2ed,roughness:.245,metalness:0,clearcoat:.45,clearcoatRoughness:.18,ior:1.46,envMapIntensity:.8});
      const titanium=new THREE.MeshStandardMaterial({color:0xaeb1b4,roughness:.24,metalness:1,envMapIntensity:1.15});
      const polished=new THREE.MeshStandardMaterial({color:0xbec0c2,roughness:.16,metalness:1,envMapIntensity:1.2});

      // A continuous sculpted crown. No extruded outline, boxes, or attached lobes.
      const profile=new THREE.CatmullRomCurve3([
        [0,.075],[.35,.075],[.60,.10],[.73,.22],[.82,.48],[.88,.88],
        [.90,1.28],[.87,1.60],[.76,1.82],[.54,1.88],[.28,1.84],[0,1.82]
      ].map(([r,y])=>new THREE.Vector3(r,y,0)),false,'centripetal');
      const radial=128,rings=112,positions=[],indices=[];
      for(let j=0;j<=rings;j++){
        const p=profile.getPoint(j/rings);
        for(let i=0;i<=radial;i++){
          const a=2*Math.PI*i/radial,co=Math.cos(a),si=Math.sin(a);
          let x=p.x*Math.sign(co)*Math.pow(Math.abs(co),.86);
          let z=p.x*.79*Math.sign(si)*Math.pow(Math.abs(si),.86);
          let y=p.y;
          const top=THREE.MathUtils.smoothstep(y,1.48,1.84);
          let cusps=0;
          for(const sx of [-1,1])for(const sz of [-1,1])
            cusps+=Math.exp(-((x-sx*.46)**2/.085+(z-sz*.32)**2/.065))*(sz>0?.12:.095);
          y+=top*(cusps-.042*Math.exp(-x*x/.022));
          const groove=.040*Math.exp(-x*x/.022)*Math.exp(-(((y-1.19)/.66)**4));
          z-=Math.sign(z)*groove*Math.pow(Math.abs(si),8);
          positions.push(x,y,z);
          if(j<rings && i<radial){const a0=j*(radial+1)+i,b=a0+radial+1;indices.push(a0,b,a0+1,b,b+1,a0+1);}
        }
      }
      const crownGeometry=new THREE.BufferGeometry();
      crownGeometry.setAttribute('position',new THREE.Float32BufferAttribute(positions,3));
      crownGeometry.setIndex(indices);crownGeometry.computeVertexNormals();
      root.add(new THREE.Mesh(crownGeometry,ceramic));
      const lathe=(points,material)=>{
        const mesh=new THREE.Mesh(new THREE.LatheGeometry(points.map(([r,y])=>new THREE.Vector2(r,y)),128),material);
        root.add(mesh);return mesh;
      };
      lathe([[0,-.27],[.40,-.27],[.46,-.24],[.49,-.20],[.49,-.045],[.48,.10],[0,.10]],polished);
      lathe([[0,-.40],[.39,-.40],[.45,-.37],[.46,-.33],[.46,-.29],[.42,-.27],[0,-.27]],titanium);
      lathe([[0,-2.16],[.09,-2.15],[.19,-2.10],[.27,-2.03],[.32,-1.94],[.365,-1.7],[.40,-1.2],[.42,-.45],[.41,-.37],[0,-.37]],titanium);
      // Machined helical flank: a real tapered screw surface, not a coiled tube.
      const tp=[],ti=[],steps=520,sectors=128;
      for(let j=0;j<=steps;j++){
        const t=j/steps,y=-.40-1.60*t,core=.42-.10*t;
        const fade=THREE.MathUtils.smoothstep(t,0,.035)*(1-THREE.MathUtils.smoothstep(t,.90,1));
        for(let i=0;i<=sectors;i++){
          const a=i/sectors*Math.PI*2;
          const phase=((t*9.4-i/sectors)%1+1)%1;
          const ridge=Math.pow(Math.max(0,1-Math.abs(phase-.5)/.42),.78);
          const r=core+.075*ridge*fade;
          tp.push(r*Math.cos(a),y,r*Math.sin(a));
          if(j<steps && i<sectors){const q=j*(sectors+1)+i,b=q+sectors+1;ti.push(q,q+1,b,b,q+1,b+1);}
        }
      }
      const screwGeometry=new THREE.BufferGeometry();
      screwGeometry.setAttribute('position',new THREE.Float32BufferAttribute(tp,3));
      screwGeometry.setIndex(ti);screwGeometry.computeVertexNormals();
      root.add(new THREE.Mesh(screwGeometry,titanium));
      scene.add(new THREE.HemisphereLight(0xffffff,0x6b7480,1.05));
      const key=new THREE.DirectionalLight(0xffffff,2.4);key.position.set(-3,4,5);scene.add(key);
      const fill=new THREE.DirectionalLight(0xf2f6ff,.75);fill.position.set(4,2,2);scene.add(fill);
      const rim=new THREE.DirectionalLight(0xffffff,1.4);rim.position.set(2,3,-4);scene.add(rim);
      const resize=()=>{
        const box=canvas.getBoundingClientRect();
        renderer.setSize(Math.max(1,Math.round(box.width)),Math.max(1,Math.round(box.height)),false);
        camera.aspect=Math.max(1,box.width)/Math.max(1,box.height);camera.updateProjectionMatrix();
      };
      resize();new ResizeObserver(resize).observe(canvas);
      const reduce=matchMedia('(prefers-reduced-motion: reduce)');
      let visible=true,last=0,angle=-.18;
      new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;},{rootMargin:'80px'}).observe(canvas);
      const animate=time=>{
        const dt=last?Math.min((time-last)/1000,.1):0;last=time;
        if(visible&&!document.hidden){
          if(!reduce.matches)angle+=dt*.34;
          root.rotation.y=angle;
          renderer.render(scene,camera);
        }
        requestAnimationFrame(animate);
      };
      requestAnimationFrame(animate);
      renderer.render(scene,camera);
      canvas.style.background='transparent';
      canvas.dataset.ready='rendered';
    }catch(error){
      canvas.dataset.ready='error';
      console.warn('Implant renderer could not start',error);
      canvas.closest('.implant-visual')?.classList.add('implant-visual--fallback');
    }
  };

  const mountImplant = () => {
    const card = document.querySelector('.svc__lead[data-service="implant"]');
    if (!card || card.querySelector('.implant-visual')) return;
    card.classList.add('has-implant-3d');
    const visual = document.createElement('span');
    visual.className = 'implant-visual';
    visual.setAttribute('aria-hidden', 'true');
    setTrustedHTML(visual,`
      <span class="implant-visual__aura"></span>
      <span class="implant-visual__orbit"></span>
      <span class="implant-visual__frame"><canvas class="implant-visual__canvas"></canvas></span>
      <span class="implant-visual__shadow"></span>
      <span class="implant-visual__floor"></span>
      <span class="implant-visual__label">نمای سه‌بعدی ایمپلنت</span>`);
    const description = card.children[1];
    card.insertBefore(visual, description || null);
    renderDentalImplant(visual.querySelector('.implant-visual__canvas'));
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountImplant, {once:true});
  else mountImplant();
})();
