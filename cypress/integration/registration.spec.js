import { StubConfirmAndAlertBox, AssertConfirmationKeywords, 
    RetrieveAccountToken, LoginAndAssertUserIconVisibility 
} from "./registration.scripts"

describe("The registration and login process", () => {
  specify("Registration gives a token which is used for logging in", () => {
      
    StubConfirmAndAlertBox()

    cy.visit("/");
    cy.get("#join")
      .click()
      .then(() => {
        AssertConfirmationKeywords()
        RetrieveAccountToken()
      });

    cy.get("[data-test=userIcon]").should("be.visible");
    
    cy.clearCookies();
    cy.visit("/");
    LoginAndAssertUserIconVisibility()
  });
});
