<!DOCTYPE html>
<html>
  <head>
    <title>Tangent Quest</title>
  </head>
  <body>
    <h1>Welcome to Tangent Quest!</h1>
    <p>Can you find the equation of the tangent line?</p>
    
    <script>
      // Paste the code for the TangentQuest object here
      const TangentQuest = {
        <script>
    // Define the game object
const TangentQuest = {
  currentStep: 0,
  steps: [
    {
      name: "Introduction",
      description: "You are a detective trying to solve mathematical mysteries related to finding the tangent line to a curve. Can you collect all the clues and solve the puzzles to complete the game?"
    },
    {
      name: "Derivative Station",
      description: "To find the equation of the tangent line, you first need to find the derivative of the function at the given point. Use the limit definition of the derivative or differentiation rules to solve the puzzle and collect the clue for the next step.",
      puzzle: "Solve the following limit: lim(h -> 0) [(x + h)^2 - x^2] / h",
      answer: "2x"
    },
    {
      name: "Slope Station",
      description: "Now that you have the derivative, you can find the slope of the tangent line at the given point. Plug in the x-value of the given point into the derivative to solve the puzzle and collect the clue for the next step.",
      puzzle: "What is the slope of the tangent line to the function f(x) = x^2 at the point (2, 4)?",
      answer: "4"
    },
    {
      name: "Equation Station",
      description: "You now have the slope of the tangent line and the coordinates of a point on the line. Use the point-slope form of the line to write the equation of the tangent line and complete the game!",
      puzzle: "Write the equation of the tangent line to the function f(x) = x^2 at the point (2, 4)",
      answer: "y - 4 = 4(x - 2)"
    }
  ],

  // Define methods for the game object
  start: function() {
    console.log("Welcome to Tangent Quest!");
    console.log("Can you find the equation of the tangent line?");
    this.displayStep();
  },

  displayStep: function() {
    console.log(this.steps[this.currentStep].name);
    console.log(this.steps[this.currentStep].description);
    if (this.steps[this.currentStep].puzzle) {
      console.log("Puzzle: " + this.steps[this.currentStep].puzzle);
    }
  },

  checkAnswer: function(guess) {
    if (guess.toLowerCase() === this.steps[this.currentStep].answer.toLowerCase()) {
      console.log("Correct!");
      this.currentStep++;
      if (this.currentStep < this.steps.length) {
        this.displayStep();
      } else {
        console.log("Congratulations! You solved the mystery and completed Tangent Quest!");
      }
    } else {
      console.log("Sorry, that's incorrect. Try again!");
    }
  }
};

// Start the game
TangentQuest.start();
</script>
 
      };
      
  
  </body>
</html>
