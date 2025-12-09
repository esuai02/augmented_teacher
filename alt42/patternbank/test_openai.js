// OpenAI API Test Script for Node.js
// Note: PatternBank uses PHP, this is just for testing

import OpenAI from "openai";

const client = new OpenAI({
  apiKey: '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'
});

async function testOpenAI() {
  try {
    const response = await client.chat.completions.create({
      model: "gpt-4o",  // Using gpt-4o (latest available model)
      messages: [
        {
          role: "user",
          content: "Write a short bedtime story about a unicorn."
        }
      ],
      max_tokens: 500,
      temperature: 0.7
    });

    console.log("Response:", response.choices[0].message.content);
    
    // Test math problem generation (like PatternBank)
    const mathResponse = await client.chat.completions.create({
      model: "gpt-4o",
      messages: [
        {
          role: "system",
          content: "당신은 한국 고등학교 수학 교육과정 전문가입니다."
        },
        {
          role: "user",
          content: "간단한 수학 문제를 하나 생성해주세요."
        }
      ],
      max_tokens: 200,
      temperature: 0.7
    });
    
    console.log("\nMath Problem:", mathResponse.choices[0].message.content);
    
  } catch (error) {
    console.error("Error:", error.message);
  }
}

testOpenAI();