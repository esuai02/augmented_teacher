<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Avatar Demo</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://threejs.org/examples/jsm/loaders/FontLoader.js"></script>
    <style>
      button {
        display: block;
        margin-bottom: 5px;
      }
    </style>
  </head>
  <body>
    <button id="happyButton">Happy</button>
    <button id="sadButton">Sad</button>
    <button id="angryButton">Angry</button>
    <!-- Add more buttons for other emotions here -->
    <script>
      // Define scene, camera, and renderer
      var scene = new THREE.Scene();
      var camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
      camera.position.z = 5;
      var renderer = new THREE.WebGLRenderer();
      renderer.setSize(window.innerWidth, window.innerHeight);
      document.body.appendChild(renderer.domElement);

      // Define avatar mesh
      var geometry = new THREE.SphereGeometry(1, 32, 32);
      var material = new THREE.MeshBasicMaterial({ color: 0xffffff });
      var avatar = new THREE.Mesh(geometry, material);
      scene.add(avatar);

      // Add facial features
      var eyeGeometry = new THREE.SphereGeometry(0.1, 16, 16);
      var eyeMaterial = new THREE.MeshBasicMaterial({ color: 0x000000 });
      var leftEye = new THREE.Mesh(eyeGeometry, eyeMaterial);
      var rightEye = new THREE.Mesh(eyeGeometry, eyeMaterial);
      leftEye.position.set(-0.3, 0.3, 0.9);
      rightEye.position.set(0.3, 0.3, 0.9);
      avatar.add(leftEye);
      avatar.add(rightEye);

      var mouthGeometry = new THREE.TorusGeometry(0.3, 0.05, 16, 100);
      var mouthMaterial = new THREE.MeshBasicMaterial({ color: 0x000000 });
      var mouth = new THREE.Mesh(mouthGeometry, mouthMaterial);
      mouth.position.set(0, -0.3, 0.9);
      mouth.rotation.x = -Math.PI / 2;
      avatar.add(mouth);

      // Load font and create text geometry
      function loadFont(url) {
        return new Promise((resolve, reject) => {
          new THREE.FontLoader().load(url, resolve, undefined, reject);
        });
      }

      async function createTextGeometry(text, size, fontUrl) {
        const font = await loadFont(fontUrl);
        const textGeometry = new THREE.TextGeometry(text, {
          font: font,
          size: size,
          height: 0.01,
        });
        textGeometry.center();
        return textGeometry;
      }

      // Create text object to display emotion name
      createTextGeometry("Emotion", 0.2, "https://threejs.org/examples/fonts/helvetiker_regular.typeface.json").then((textGeometry) => {
          const textMaterial = new THREE.MeshBasicMaterial({ color: 0x000000 });
          const emotionText = new THREE.Mesh(textGeometry, textMaterial);
          emotionText.position.set(0, -1.5, 0);
          avatar.add(emotionText);

          function setEmotion(emotion) {
            emotionText.geometry.dispose();
            emotionText.geometry = new THREE.TextGeometry(emotion, {
              font: emotionText.geometry.parameters.font,
              size: emotionText.geometry.parameters.size,
              height: emotionText.geometry.parameters.height,
            });
            emotionText.geometry.center();

            switch (emotion) {
              case "happy":
                leftEye.scale.y = 1;
                rightEye.scale.y = 1;
                mouth.rotation.x = -Math.PI / 2;
                avatar.material.color.set(0x00ff00);
                break;
              case "sad":
                leftEye.scale.y = 0.6;
                rightEye.scale.y = 0.6;
                mouth.rotation.x = Math.PI / 2;
                avatar.material.color.set(0x0000ff);
                break;
              case "angry":
                leftEye.scale.y = 0.6;
                rightEye.scale.y = 0.6;
                mouth.rotation.x = -Math.PI;
                avatar.material.color.set(0xff0000);
                break;
              // Add more emotions here
              // ...
            }
          }


          // Add event listeners for buttons
          document.getElementById("happyButton").addEventListener("click", () => {
            setEmotion("happy");
          });
          document.getElementById("sadButton").addEventListener("click", () => {
            setEmotion("sad");
          });
          document.getElementById("angryButton").addEventListener("click", () => {
            setEmotion("angry");
          });
          // Add event listeners for other emotions here

          // Example usage
          setEmotion("happy");

          // Define an animation loop that renders the scene
          function animate() {
            requestAnimationFrame(animate);
            renderer.render(scene, camera);
          }
          animate();
        });
  
    </script>
  </body>
</html>
