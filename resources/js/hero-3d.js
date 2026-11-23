import * as THREE from 'three';

/**
 * Mount procedural low-poly Three.js stage (Scales of Justice + Transparency Prism)
 * on the desktop hero after idle, respecting motion preferences and battery budget.
 *
 * @param {HTMLElement} container
 * @returns {{ setSlide: (index: number) => void, destroy: () => void } | null}
 */
export function mountHero3D(container) {
    if (!container || typeof window === 'undefined') {
        return null;
    }

    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({
            alpha: true,
            antialias: true,
            powerPreference: 'low-power',
        });
    } catch {
        return null;
    }

    const width = Math.max(240, container.clientWidth || 340);
    const height = Math.max(220, container.clientHeight || 280);

    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.setSize(width, height);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.domElement.setAttribute('data-hero-3d-canvas', '1');
    renderer.domElement.setAttribute('aria-hidden', 'true');
    renderer.domElement.className = 'h-full w-full block opacity-0 transition-opacity duration-500';

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(40, width / height, 0.1, 40);
    camera.position.set(0, 0.4, 5.4);

    const ambient = new THREE.AmbientLight(0xd1fae5, 1.15);
    scene.add(ambient);

    const keyLight = new THREE.DirectionalLight(0xffcf70, 2.1);
    keyLight.position.set(3.2, 4.5, 4.0);
    scene.add(keyLight);

    const rimLight = new THREE.DirectionalLight(0x0a946b, 1.6);
    rimLight.position.set(-3.5, -1.5, 2.0);
    scene.add(rimLight);

    const emeraldMat = new THREE.MeshStandardMaterial({
        color: 0x056e4f,
        roughness: 0.28,
        metalness: 0.35,
    });
    const deepMat = new THREE.MeshStandardMaterial({
        color: 0x0a946b,
        roughness: 0.35,
        metalness: 0.25,
    });
    const goldMat = new THREE.MeshStandardMaterial({
        color: 0xff9900,
        roughness: 0.24,
        metalness: 0.72,
    });
    const wireMat = new THREE.MeshBasicMaterial({
        color: 0xffcf70,
        wireframe: true,
        transparent: true,
        opacity: 0.32,
    });

    // Slide 0 Sculpture: Balanced Scales of Justice (Timbangan Keadilan)
    const scalesGroup = new THREE.Group();

    const plinth = new THREE.Mesh(new THREE.CylinderGeometry(1.05, 1.25, 0.24, 24), emeraldMat);
    plinth.position.y = -1.28;
    scalesGroup.add(plinth);

    const plinthTrim = new THREE.Mesh(new THREE.TorusGeometry(1.12, 0.04, 12, 32), goldMat);
    plinthTrim.rotation.x = Math.PI / 2;
    plinthTrim.position.y = -1.16;
    scalesGroup.add(plinthTrim);

    const pillar = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.12, 2.4, 16), goldMat);
    pillar.position.y = -0.05;
    scalesGroup.add(pillar);

    const apex = new THREE.Mesh(new THREE.OctahedronGeometry(0.18, 0), goldMat);
    apex.position.y = 1.25;
    scalesGroup.add(apex);

    const beamPivot = new THREE.Group();
    beamPivot.position.y = 0.92;
    const beam = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.05, 2.3, 16), goldMat);
    beam.rotation.z = Math.PI / 2;
    beamPivot.add(beam);

    const createPan = (xOffset) => {
        const panGroup = new THREE.Group();
        panGroup.position.set(xOffset, -0.02, 0);

        const cordGeom = new THREE.CylinderGeometry(0.015, 0.015, 0.85, 8);
        const cordL = new THREE.Mesh(cordGeom, wireMat);
        cordL.position.set(-0.18, -0.42, 0);
        cordL.rotation.z = -0.2;
        panGroup.add(cordL);

        const cordR = new THREE.Mesh(cordGeom, wireMat);
        cordR.position.set(0.18, -0.42, 0);
        cordR.rotation.z = 0.2;
        panGroup.add(cordR);

        const bowl = new THREE.Mesh(new THREE.CylinderGeometry(0.42, 0.18, 0.16, 20), emeraldMat);
        bowl.position.y = -0.88;
        panGroup.add(bowl);

        const bowlRim = new THREE.Mesh(new THREE.TorusGeometry(0.42, 0.025, 10, 24), goldMat);
        bowlRim.rotation.x = Math.PI / 2;
        bowlRim.position.y = -0.8;
        panGroup.add(bowlRim);

        return panGroup;
    };

    const leftPan = createPan(-1.02);
    const rightPan = createPan(1.02);
    beamPivot.add(leftPan, rightPan);
    scalesGroup.add(beamPivot);

    // Slide 1+ Sculpture: Isometric Transparency Prism (Prisma Keterbukaan)
    const prismGroup = new THREE.Group();
    prismGroup.visible = false;

    const outerCrystal = new THREE.Mesh(new THREE.OctahedronGeometry(1.15, 0), deepMat);
    const crystalWire = new THREE.Mesh(new THREE.OctahedronGeometry(1.24, 1), wireMat);
    const innerCore = new THREE.Mesh(new THREE.DodecahedronGeometry(0.55, 0), goldMat);
    const haloRing = new THREE.Mesh(new THREE.TorusGeometry(1.48, 0.035, 12, 32), goldMat);
    haloRing.rotation.x = Math.PI / 3;
    prismGroup.add(outerCrystal, crystalWire, innerCore, haloRing);

    // Instanced golden motes (single draw call)
    const moteCount = 16;
    const moteGeom = new THREE.SphereGeometry(0.035, 8, 8);
    const motes = new THREE.InstancedMesh(moteGeom, goldMat, moteCount);
    const dummy = new THREE.Object3D();
    const moteSeeds = Array.from({ length: moteCount }, (_, i) => ({
        angle: (i / moteCount) * Math.PI * 2,
        radius: 1.55 + (i % 4) * 0.28,
        y: -0.9 + ((i * 0.23) % 2.1),
        speed: 0.3 + (i % 3) * 0.12,
    }));

    const stageRoot = new THREE.Group();
    stageRoot.add(scalesGroup, prismGroup, motes);
    scene.add(stageRoot);

    container.appendChild(renderer.domElement);
    requestAnimationFrame(() => {
        renderer.domElement.classList.remove('opacity-0');
        const fallback = container.querySelector('[data-hero-3d-fallback]');
        if (fallback) {
            fallback.classList.add('opacity-15');
        }
    });

    let pointerX = 0;
    let pointerY = 0;
    let targetX = 0;
    let targetY = 0;
    let isVisible = true;
    let rafId = null;
    let disposed = false;
    const clock = new THREE.Clock();

    const onMouseMove = (event) => {
        const rect = container.getBoundingClientRect();
        if (!rect.width || !rect.height) {
            return;
        }
        targetX = ((event.clientX - rect.left) / rect.width - 0.5) * 0.45;
        targetY = ((event.clientY - rect.top) / rect.height - 0.5) * 0.25;
    };

    const onResize = () => {
        if (disposed || !container) {
            return;
        }
        const w = Math.max(240, container.clientWidth || width);
        const h = Math.max(220, container.clientHeight || height);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    };

    const renderFrame = () => {
        if (disposed) {
            return;
        }

        const t = clock.getElapsedTime();
        pointerX += (targetX - pointerX) * 0.05;
        pointerY += (targetY - pointerY) * 0.05;

        stageRoot.rotation.y = Math.sin(t * 0.35) * 0.24 + pointerX;
        stageRoot.rotation.x = pointerY * 0.6;

        if (scalesGroup.visible) {
            const sway = Math.sin(t * 1.1) * 0.055;
            beamPivot.rotation.z = sway;
            leftPan.rotation.z = -sway;
            rightPan.rotation.z = -sway;
        }

        if (prismGroup.visible) {
            outerCrystal.rotation.y = t * 0.45;
            crystalWire.rotation.y = -t * 0.28;
            innerCore.rotation.x = t * 0.6;
            haloRing.rotation.z = t * 0.35;
        }

        moteSeeds.forEach((seed, idx) => {
            const a = seed.angle + t * seed.speed * 0.25;
            dummy.position.set(
                Math.cos(a) * seed.radius,
                seed.y + Math.sin(t * 0.8 + idx) * 0.14,
                Math.sin(a) * seed.radius * 0.55
            );
            const scale = 0.75 + Math.sin(t * 1.6 + idx) * 0.25;
            dummy.scale.setScalar(scale);
            dummy.updateMatrix();
            motes.setMatrixAt(idx, dummy.matrix);
        });
        motes.instanceMatrix.needsUpdate = true;

        renderer.render(scene, camera);

        if (isVisible && !document.hidden) {
            rafId = window.requestAnimationFrame(renderFrame);
        } else {
            rafId = null;
        }
    };

    const startLoop = () => {
        if (!disposed && rafId === null && isVisible && !document.hidden) {
            rafId = window.requestAnimationFrame(renderFrame);
        }
    };

    const stopLoop = () => {
        if (rafId !== null) {
            window.cancelAnimationFrame(rafId);
            rafId = null;
        }
    };

    const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries) => {
              const entry = entries[0];
              isVisible = Boolean(entry?.isIntersecting);
              if (isVisible) {
                  startLoop();
              } else {
                  stopLoop();
              }
          }, { threshold: 0.05 })
        : null;

    observer?.observe(container);

    const onVisibilityChange = () => {
        if (document.hidden) {
            stopLoop();
        } else {
            startLoop();
        }
    };

    window.addEventListener('mousemove', onMouseMove, { passive: true });
    window.addEventListener('resize', onResize, { passive: true });
    document.addEventListener('visibilitychange', onVisibilityChange);

    startLoop();

    return {
        setSlide(index) {
            const usePrism = Number(index) % 2 === 1;
            scalesGroup.visible = !usePrism;
            prismGroup.visible = usePrism;
            if (!isVisible || document.hidden) {
                renderer.render(scene, camera);
            }
        },
        destroy() {
            if (disposed) {
                return;
            }
            disposed = true;
            stopLoop();
            observer?.disconnect();
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('resize', onResize);
            document.removeEventListener('visibilitychange', onVisibilityChange);

            scene.traverse((obj) => {
                if (obj.geometry) {
                    obj.geometry.dispose();
                }
                if (obj.material) {
                    if (Array.isArray(obj.material)) {
                        obj.material.forEach((m) => m.dispose());
                    } else {
                        obj.material.dispose();
                    }
                }
            });
            renderer.dispose();
            renderer.domElement.remove();

            const fallback = container.querySelector('[data-hero-3d-fallback]');
            if (fallback) {
                fallback.classList.remove('opacity-15');
            }
        },
    };
}

