import React from "react"
import { graphql } from "gatsby"

export const AuthorFragment = graphql`
  fragment AuthorBio on WpUser {
    id
    name
    description
  }
`

const AuthorBio = ({ author: { id, name, description } }) => {
  return (
    <div className={`author-${id}`}>
      <h2>{name}</h2>
      <div dangerouslySetInnerHTML={{ __html: description }} />
    </div>
  )
}

export default AuthorBio
